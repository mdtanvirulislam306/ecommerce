<?php

namespace Modules\Marketing\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Marketing\Models\Story;

class StoryService extends Service
{
    public function listPaginated(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        return Story::query()
            ->with('user:id,name')
            ->when($search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Story $story) => $this->formatForList($story));
    }

    public function create(array $data, UploadedFile $media, int $userId): Story
    {
        return DB::transaction(function () use ($data, $media, $userId) {
            $path = $media->store('stories', 'public');

            return Story::query()->create([
                'user_id' => $userId,
                'title' => $data['title'] ?? null,
                'type' => $data['type'],
                'media_path' => $path,
                'action_url' => $data['action_url'] ?? null,
                'action_label' => $data['action_label'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'starts_at' => $data['starts_at'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
            ]);
        });
    }

    public function delete(Story $story): void
    {
        DB::transaction(function () use ($story) {
            if ($story->media_path) {
                Storage::disk('public')->delete($story->media_path);
            }

            $story->delete();
        });
    }

    public function update(Story $story, array $data, ?UploadedFile $media = null): Story
    {
        return DB::transaction(function () use ($story, $data, $media) {
            if ($media) {
                if ($story->media_path) {
                    Storage::disk('public')->delete($story->media_path);
                }

                $story->media_path = $media->store('stories', 'public');
                $story->type = $data['type'];
            }

            $story->update([
                'title' => $data['title'] ?? null,
                'type' => $data['type'],
                'action_url' => $data['action_url'] ?? null,
                'action_label' => $data['action_label'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'starts_at' => $data['starts_at'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
            ]);

            return $story->fresh();
        });
    }

    public function formatForDetail(Story $story): array
    {
        $story->loadMissing('user:id,name');

        return [
            'id' => $story->id,
            'title' => $story->title,
            'type' => $story->type,
            'media_url' => $story->mediaUrl(),
            'action_url' => $story->action_url,
            'action_label' => $story->action_label,
            'is_active' => $story->is_active,
            'starts_at' => $story->starts_at?->timezone(config('app.timezone'))->format('Y-m-d\TH:i'),
            'expires_at' => $story->expires_at?->timezone(config('app.timezone'))->format('Y-m-d\TH:i'),
            'is_expired' => $story->isExpired(),
            'visibility_status' => $story->visibilityStatus(),
            'author' => $story->user?->name,
            'created_at' => $story->created_at?->toIso8601String(),
        ];
    }

    private function formatDateTime(?Carbon $date): ?string
    {
        return $date?->timezone(config('app.timezone'))->toIso8601String();
    }

    private function formatForList(Story $story): array
    {
        return [
            'id' => $story->id,
            'title' => $story->title,
            'type' => $story->type,
            'media_url' => $story->mediaUrl(),
            'action_url' => $story->action_url,
            'action_label' => $story->action_label,
            'is_active' => $story->is_active,
            'starts_at' => $this->formatDateTime($story->starts_at),
            'expires_at' => $this->formatDateTime($story->expires_at),
            'is_expired' => $story->isExpired(),
            'visibility_status' => $story->visibilityStatus(),
            'author' => $story->user?->name,
            'created_at' => $story->created_at?->toIso8601String(),
        ];
    }
}
