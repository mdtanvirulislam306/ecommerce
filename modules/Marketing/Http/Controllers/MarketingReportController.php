<?php

namespace Modules\Marketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Marketing\Enums\CampaignChannel;
use Modules\Marketing\Services\CampaignService;

class MarketingReportController extends Controller
{
    public function index(CampaignService $campaigns): Response
    {
        $stats = $campaigns->reportStats();

        return Inertia::render('Marketing/Reports/Index', [
            'stats' => $stats,
            'channels' => collect(CampaignChannel::cases())->map(fn (CampaignChannel $channel) => [
                'value' => $channel->value,
                'label' => $channel->label(),
                'count' => $stats['campaigns_by_channel'][$channel->value] ?? 0,
            ])->all(),
        ]);
    }
}
