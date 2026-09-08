<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Marketing\Models\Story;

class StoriesSeeder extends Seeder
{
    /**
     * One shop story per calendar day.
     *
     * @var list<array{title: string, category: string, image: string}>
     */
    private array $stories = [
        ['title' => "Today's Fresh Picks", 'category' => 'grocery', 'image' => 'grocery.jpg'],
        ['title' => 'Fruit Basket Deal', 'category' => 'fruits-vegetables', 'image' => 'fruits-vegetables.jpg'],
        ['title' => 'Meat & Fish Special', 'category' => 'meat-fish', 'image' => 'meat-fish.jpg'],
        ['title' => 'Cooking Essentials', 'category' => 'cooking', 'image' => 'cooking.jpg'],
        ['title' => 'Sauces & Pickles', 'category' => 'sauces-pickles', 'image' => 'sauces-pickles.jpg'],
        ['title' => 'Dairy Morning', 'category' => 'dairy-eggs', 'image' => 'dairy-eggs.jpg'],
        ['title' => 'Breakfast Combo', 'category' => 'breakfast', 'image' => 'breakfast.jpg'],
        ['title' => 'Sweet Treats', 'category' => 'candy-chocolate', 'image' => 'candy-chocolate.jpg'],
        ['title' => 'Snack Attack', 'category' => 'snacks', 'image' => 'snacks.jpg'],
        ['title' => 'Cool Beverages', 'category' => 'beverages', 'image' => 'beverages.jpg'],
        ['title' => 'Baking Night', 'category' => 'baking', 'image' => 'baking.jpg'],
        ['title' => 'Flour Stock', 'category' => 'flour', 'image' => 'flour.jpg'],
        ['title' => 'Frozen Favourites', 'category' => 'frozen-canned', 'image' => 'frozen-canned.jpg'],
        ['title' => 'Nuts & Dry Fruits', 'category' => 'nuts-dried-fruits', 'image' => 'nuts-dried-fruits.jpg'],
        ['title' => 'Weekend Grocery', 'category' => 'grocery', 'image' => 'grocery.jpg'],
    ];

    public function run(): void
    {
        $userId = User::query()->where('email', 'admin@admin.com')->value('id')
            ?? User::query()->value('id');

        if ($userId === null) {
            return;
        }

        $takenDates = $this->occupiedDates();
        $cursor = now()->startOfDay();
        $created = 0;

        foreach ($this->stories as $index => $item) {
            if (Story::query()->where('title', $item['title'])->exists()) {
                continue;
            }

            while (isset($takenDates[$cursor->toDateString()])) {
                $cursor = $cursor->copy()->subDay();
            }

            $startsAt = $cursor->copy()->setTime(10, 0);
            $takenDates[$cursor->toDateString()] = true;
            $mediaPath = $this->storeStoryImage($item['image'], $item['title']);

            Story::query()->create([
                'user_id' => $userId,
                'title' => $item['title'],
                'type' => 'image',
                'media_path' => $mediaPath,
                'action_url' => '/shop?category='.$item['category'],
                'action_label' => 'Shop Now',
                'is_active' => true,
                'starts_at' => $startsAt,
                'expires_at' => $startsAt->copy()->addMonths(6),
                'sort_order' => $index + 1,
            ]);

            $created++;
            $cursor = $cursor->copy()->subDay();
        }

        $this->command?->info("Stories seeded: {$created}.");
    }

    /**
     * @return array<string, true>
     */
    private function occupiedDates(): array
    {
        $dates = [];

        foreach (Story::query()->get(['starts_at', 'created_at']) as $story) {
            $anchor = $story->starts_at ?? $story->created_at;

            if ($anchor === null) {
                continue;
            }

            $dates[$anchor->toDateString()] = true;
        }

        return $dates;
    }

    private function storeStoryImage(string $filename, string $title): string
    {
        $source = database_path('seeders/fixtures/categories/'.$filename);
        $path = 'stories/seed-'.Str::slug($title).'.jpg';

        if (is_file($source)) {
            Storage::disk('public')->put($path, (string) file_get_contents($source));
        } elseif (! Storage::disk('public')->exists($path)) {
            Storage::disk('public')->put($path, $this->placeholderJpeg());
        }

        return $path;
    }

    private function placeholderJpeg(): string
    {
        return (string) base64_decode('
            /9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0a
            HBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIy
            MjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIA
            AhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAn/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEB
            AQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAGcP//Z
        ', true);
    }
}
