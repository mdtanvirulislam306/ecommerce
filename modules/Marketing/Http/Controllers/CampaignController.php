<?php

namespace Modules\Marketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Marketing\Enums\CampaignChannel;
use Modules\Marketing\Enums\CampaignStatus;
use Modules\Marketing\Http\Requests\StoreCampaignRequest;
use Modules\Marketing\Http\Requests\UpdateCampaignRequest;
use Modules\Marketing\Models\Campaign;
use Modules\Marketing\Services\CampaignService;

class CampaignController extends Controller
{
    public function index(Request $request, CampaignService $campaigns): Response
    {
        return Inertia::render('Marketing/Campaigns/Index', [
            'campaigns' => $campaigns->listPaginated(
                $request->string('search')->trim()->toString() ?: null,
                $request->integer('per_page', 25),
                $request->string('channel')->toString() ?: null,
            ),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'per_page' => $request->integer('per_page', 25),
                'channel' => $request->string('channel')->toString(),
            ],
            'channels' => collect(CampaignChannel::cases())->map(fn ($c) => [
                'value' => $c->value,
                'label' => $c->label(),
            ])->all(),
            'statuses' => collect(CampaignStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ])->all(),
        ]);
    }

    public function channelIndex(Request $request, CampaignService $campaigns, string $channel): Response
    {
        $channelEnum = CampaignChannel::from($channel);

        return Inertia::render('Marketing/Channels/Index', [
            'channel' => $channelEnum->value,
            'channelLabel' => $channelEnum->label(),
            'campaigns' => $campaigns->listPaginated(
                $request->string('search')->trim()->toString() ?: null,
                $request->integer('per_page', 25),
                $channelEnum->value,
            ),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'per_page' => $request->integer('per_page', 25),
            ],
            'statuses' => collect(CampaignStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ])->all(),
            'routes' => $this->channelRouteNames($channelEnum->value),
        ]);
    }

    public function store(StoreCampaignRequest $request, CampaignService $campaigns): RedirectResponse
    {
        $campaigns->create($request->validated(), $request->user()?->id);

        return back()->with('success', 'Campaign created.');
    }

    public function channelStore(Request $request, CampaignService $campaigns, string $channel): RedirectResponse
    {
        CampaignChannel::from($channel);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:20000'],
            'scheduled_at' => ['nullable', 'date'],
            'audience_count' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['channel'] = $channel;

        $campaigns->create($data, $request->user()?->id);

        return back()->with('success', 'Campaign created.');
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign, CampaignService $campaigns): RedirectResponse
    {
        $campaigns->update($campaign, $request->validated());

        return back()->with('success', 'Campaign updated.');
    }

    public function channelUpdate(Request $request, Campaign $campaign, CampaignService $campaigns, string $channel): RedirectResponse
    {
        $this->ensureCampaignChannel($campaign, $channel);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(CampaignStatus::class)],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:20000'],
            'scheduled_at' => ['nullable', 'date'],
            'audience_count' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['channel'] = $channel;

        $campaigns->update($campaign, $data);

        return back()->with('success', 'Campaign updated.');
    }

    public function markSent(Campaign $campaign, CampaignService $campaigns): RedirectResponse
    {
        $campaigns->markSent($campaign);

        return back()->with('success', 'Campaign marked as sent (delivery provider not wired yet).');
    }

    public function channelMarkSent(Campaign $campaign, CampaignService $campaigns, string $channel): RedirectResponse
    {
        $this->ensureCampaignChannel($campaign, $channel);

        $campaigns->markSent($campaign);

        return back()->with('success', 'Campaign marked as sent (delivery provider not wired yet).');
    }

    public function destroy(Campaign $campaign, CampaignService $campaigns): RedirectResponse
    {
        $campaigns->delete($campaign);

        return back()->with('success', 'Campaign deleted.');
    }

    public function channelDestroy(Campaign $campaign, CampaignService $campaigns, string $channel): RedirectResponse
    {
        $this->ensureCampaignChannel($campaign, $channel);

        $campaigns->delete($campaign);

        return back()->with('success', 'Campaign deleted.');
    }

    private function ensureCampaignChannel(Campaign $campaign, string $channel): void
    {
        abort_unless($campaign->channel?->value === $channel, 404);
    }

    /**
     * @return array{index: string, store: string, update: string, destroy: string, markSent: string}
     */
    private function channelRouteNames(string $channel): array
    {
        return [
            'index' => "marketing.{$channel}.index",
            'store' => "marketing.{$channel}.store",
            'update' => "marketing.{$channel}.update",
            'destroy' => "marketing.{$channel}.destroy",
            'markSent' => "marketing.{$channel}.mark-sent",
        ];
    }
}
