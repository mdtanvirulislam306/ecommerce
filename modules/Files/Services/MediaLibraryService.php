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
            ->through(fn (MediaLibraryItem $row) => $this->toArray($row));
    }

    /**
     * @return list<array{id: int, name: string, path: string, url: string, mime: string, size: int}>
     */
    public function pickerItems(?string $search = null, int $limit = 60): array
    {
        return MediaLibraryItem::query()
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (MediaLibraryItem $row) => $this->toArray($row))
            ->values()
            ->all();
    }

    public function upload(UploadedFile $file, ?int $userId = null): MediaLibraryItem
    {
        $path = $file->store('media', 'public');
        $mime = $file->getMimeType() ?: $file->getClientMimeType() ?: 'application/octet-stream';

        return MediaLibraryItem::query()->create([
            'name' => $file->getClientOriginalName(),
            'disk' => 'public',
            'path' => $path,
            'mime' => $this->normalizeMime($mime, $path, $file->getClientOriginalName()),
            'size' => $file->getSize() ?: 0,
            'uploaded_by' => $userId,
        ]);
    }

    /**
     * @param  list<UploadedFile>  $files
     * @return list<MediaLibraryItem>
     */
    public function uploadMany(array $files, ?int $userId = null): array
    {
        $items = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $items[] = $this->upload($file, $userId);
            }
        }

        return $items;
    }

    public function delete(MediaLibraryItem $item): void
    {
        Storage::disk($item->disk)->delete($item->path);
        $item->delete();
    }

    /**
     * @return array{id: int, name: string, path: string, url: string, mime: string, size: int}
     */
    public function toArray(MediaLibraryItem $item): array
    {
        $mime = $this->normalizeMime($item->mime, $item->path, $item->name);

        return [
            'id' => $item->id,
            'name' => $item->name,
            'path' => $item->path,
            'url' => $this->publicUrl($item->path),
            'mime' => $mime,
            'size' => (int) $item->size,
        ];
    }

    /**
     * Host-relative URL — works on ecommerce.test, localhost, artisan serve, etc.
     */
    public function publicUrl(string $path): string
    {
        return '/storage/'.ltrim(str_replace('\\', '/', $path), '/');
    }

    private function normalizeMime(?string $mime, string $path, string $name = ''): string
    {
        if (is_string($mime) && $mime !== '' && $mime !== 'application/octet-stream') {
            return $mime;
        }

        $ext = strtolower(pathinfo($path !== '' ? $path : $name, PATHINFO_EXTENSION));

        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'pdf' => 'application/pdf',
            default => $mime ?: 'application/octet-stream',
        };
    }
}
