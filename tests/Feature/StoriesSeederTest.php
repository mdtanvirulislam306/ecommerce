<?php

namespace Tests\Feature;

use Database\Seeders\AdminUserSeeder;
use Database\Seeders\StoriesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoriesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_stories_seeder_creates_fifteen_stories_on_unique_dates(): void
    {
        Storage::fake('public');
        $this->travelTo(now()->setDate(2026, 9, 8)->setTime(12, 0));

        $this->seed(AdminUserSeeder::class);
        $this->seed(StoriesSeeder::class);

        $this->assertSame(15, (int) DB::table('stories')->count());

        $dates = DB::table('stories')
            ->selectRaw('DATE(starts_at) as d')
            ->pluck('d');

        $this->assertSame(15, $dates->unique()->count());
        $this->assertTrue($dates->contains('2026-09-08'));
        Storage::disk('public')->assertExists('stories/seed-todays-fresh-picks.jpg');
    }

    public function test_stories_seeder_skips_dates_that_already_have_a_story(): void
    {
        Storage::fake('public');
        $this->travelTo(now()->setDate(2026, 9, 8)->setTime(12, 0));
        $this->seed(AdminUserSeeder::class);

        $userId = DB::table('users')->where('email', 'admin@admin.com')->value('id');
        DB::table('stories')->insert([
            'user_id' => $userId,
            'title' => 'Existing Morning Story',
            'type' => 'image',
            'media_path' => 'stories/existing.jpg',
            'is_active' => true,
            'starts_at' => '2026-09-08 09:00:00',
            'expires_at' => null,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->seed(StoriesSeeder::class);

        $this->assertSame(16, (int) DB::table('stories')->count());
        $this->assertSame(
            16,
            DB::table('stories')->selectRaw('DATE(COALESCE(starts_at, created_at)) as d')->pluck('d')->unique()->count(),
        );

        $firstSeededStartsAt = DB::table('stories')->where('title', "Today's Fresh Picks")->value('starts_at');

        $this->assertNotNull($firstSeededStartsAt);
        $this->assertSame('2026-09-07', Carbon::parse($firstSeededStartsAt)->toDateString());
    }

    public function test_stories_seeder_does_not_duplicate_on_second_run(): void
    {
        Storage::fake('public');
        $this->travelTo(now()->setDate(2026, 9, 8)->setTime(12, 0));

        $this->seed(AdminUserSeeder::class);
        $this->seed(StoriesSeeder::class);
        $this->seed(StoriesSeeder::class);

        $this->assertSame(15, (int) DB::table('stories')->count());
    }
}
