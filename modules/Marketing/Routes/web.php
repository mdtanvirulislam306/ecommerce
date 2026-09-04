<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Marketing\Http\Controllers\CampaignController;
use Modules\Marketing\Http\Controllers\MarketingCouponController;
use Modules\Marketing\Http\Controllers\MarketingLoyaltyController;
use Modules\Marketing\Http\Controllers\MarketingOverviewController;
use Modules\Marketing\Http\Controllers\MarketingPromotionController;
use Modules\Marketing\Http\Controllers\MarketingReferralController;
use Modules\Marketing\Http\Controllers\MarketingReportController;
use Modules\Marketing\Http\Controllers\SegmentController;
use Modules\Marketing\Http\Controllers\StoryController;
use Modules\Marketing\Models\Campaign;
use Modules\Marketing\Services\CampaignService;

Route::get('/overview', [MarketingOverviewController::class, 'index'])->name('overview');

Route::get('/stories', [StoryController::class, 'index'])->name('stories.index');
Route::get('/stories/create', [StoryController::class, 'create'])->name('stories.create');
Route::post('/stories', [StoryController::class, 'store'])->name('stories.store');
Route::get('/stories/{story}', [StoryController::class, 'show'])->name('stories.show');
Route::get('/stories/{story}/edit', [StoryController::class, 'edit'])->name('stories.edit');
Route::match(['put', 'patch'], '/stories/{story}', [StoryController::class, 'update'])->name('stories.update');
Route::delete('/stories/{story}', [StoryController::class, 'destroy'])->name('stories.destroy');

Route::prefix('campaigns')->name('campaigns.')->group(function () {
    Route::get('/', [CampaignController::class, 'index'])->name('index');
    Route::post('/', [CampaignController::class, 'store'])->name('store');
    Route::put('/{campaign}', [CampaignController::class, 'update'])->name('update');
    Route::post('/{campaign}/mark-sent', [CampaignController::class, 'markSent'])->name('mark-sent');
    Route::delete('/{campaign}', [CampaignController::class, 'destroy'])->name('destroy');
});

foreach (['email', 'sms', 'whatsapp', 'push'] as $channel) {
    Route::prefix($channel)->name("{$channel}.")->group(function () use ($channel) {
        Route::get('/', function (Request $request, CampaignService $campaigns) use ($channel) {
            return app(CampaignController::class)->channelIndex($request, $campaigns, $channel);
        })->name('index');

        Route::post('/', function (Request $request, CampaignService $campaigns) use ($channel) {
            return app(CampaignController::class)->channelStore($request, $campaigns, $channel);
        })->name('store');

        Route::put('/{campaign}', function (Request $request, Campaign $campaign, CampaignService $campaigns) use ($channel) {
            return app(CampaignController::class)->channelUpdate($request, $campaign, $campaigns, $channel);
        })->name('update');

        Route::post('/{campaign}/mark-sent', function (Campaign $campaign, CampaignService $campaigns) use ($channel) {
            return app(CampaignController::class)->channelMarkSent($campaign, $campaigns, $channel);
        })->name('mark-sent');

        Route::delete('/{campaign}', function (Campaign $campaign, CampaignService $campaigns) use ($channel) {
            return app(CampaignController::class)->channelDestroy($campaign, $campaigns, $channel);
        })->name('destroy');
    });
}

Route::prefix('segments')->name('segments.')->group(function () {
    Route::get('/', [SegmentController::class, 'index'])->name('index');
    Route::post('/', [SegmentController::class, 'store'])->name('store');
    Route::put('/{segment}', [SegmentController::class, 'update'])->name('update');
    Route::delete('/{segment}', [SegmentController::class, 'destroy'])->name('destroy');
});

Route::prefix('promotions')->name('promotions.')->group(function () {
    Route::get('/', [MarketingPromotionController::class, 'index'])->name('index');
    Route::post('/', [MarketingPromotionController::class, 'store'])->name('store');
    Route::put('/{promotion}', [MarketingPromotionController::class, 'update'])->name('update');
    Route::delete('/{promotion}', [MarketingPromotionController::class, 'destroy'])->name('destroy');
});

Route::prefix('coupons')->name('coupons.')->group(function () {
    Route::get('/', [MarketingCouponController::class, 'index'])->name('index');
    Route::post('/', [MarketingCouponController::class, 'store'])->name('store');
    Route::put('/{coupon}', [MarketingCouponController::class, 'update'])->name('update');
    Route::delete('/{coupon}', [MarketingCouponController::class, 'destroy'])->name('destroy');
});

Route::prefix('loyalty')->name('loyalty.')->group(function () {
    Route::get('/', [MarketingLoyaltyController::class, 'index'])->name('index');
    Route::post('/', [MarketingLoyaltyController::class, 'store'])->name('store');
    Route::put('/{loyaltySetting}', [MarketingLoyaltyController::class, 'update'])->name('update');
    Route::delete('/{loyaltySetting}', [MarketingLoyaltyController::class, 'destroy'])->name('destroy');
});

Route::prefix('referrals')->name('referrals.')->group(function () {
    Route::get('/', [MarketingReferralController::class, 'index'])->name('index');
    Route::post('/', [MarketingReferralController::class, 'store'])->name('store');
    Route::put('/{referral}', [MarketingReferralController::class, 'update'])->name('update');
    Route::delete('/{referral}', [MarketingReferralController::class, 'destroy'])->name('destroy');
});

Route::get('/reports', [MarketingReportController::class, 'index'])->name('reports');
