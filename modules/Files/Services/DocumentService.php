<?php

namespace Modules\Files\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Files\Models\Document;

class DocumentService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return Document::query()
            ->when($search, fn ($q, $search) => $q->where('title', 'like', "%{$search}%"))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Document $row) => [
                'id' => $row->id,
                'title' => $row->title,
                'path' => $row->path,
                'url' => Storage::disk($row->disk)->url($row->path),
                'mime' => $row->mime,
                'size' => $row->size,
            ]);
    }

    public function upload(string $title, UploadedFile $file, ?int $userId = null): Document
    {
        $path = $file->store('documents', 'public');

        return Document::query()->create([
            'title' => $title,
            'disk' => 'public',
            'path' => $path,
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize() ?: 0,
            'uploaded_by' => $userId,
        ]);
    }

    public function delete(Document $document): void
    {
        Storage::disk($document->disk)->delete($document->path);
        $document->delete();
    }
}
