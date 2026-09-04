<?php

namespace Modules\Marketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Marketing\Http\Requests\StoreStoryRequest;
use Modules\Marketing\Http\Requests\UpdateStoryRequest;
use Modules\Marketing\Models\Story;
use Modules\Marketing\Services\StoryService;

class StoryController extends Controller
{
    public function index(Request $request, StoryService $service): Response
    {
        $perPage = (int) $request->input('per_page', 10);
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Marketing/Stories/Index', [
            'stories' => $service->listPaginated($search, $perPage),
            'filters' => [
                'search' => $search,
                'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10,
            ],
            'perPageOptions' => [10, 25, 50, 100],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Marketing/Stories/Create');
    }

    public function store(StoreStoryRequest $request, StoryService $service): RedirectResponse
    {
        $service->create(
            $request->validated(),
            $request->file('media'),
            $request->user()->id,
        );

        return redirect()
            ->route('marketing.stories.index')
            ->with('success', 'Story published successfully.');
    }

    public function show(Story $story, StoryService $service): Response
    {
        return Inertia::render('Marketing/Stories/Show', [
            'story' => $service->formatForDetail($story),
        ]);
    }

    public function edit(Story $story, StoryService $service): Response
    {
        return Inertia::render('Marketing/Stories/Edit', [
            'story' => $service->formatForDetail($story),
        ]);
    }

    public function update(UpdateStoryRequest $request, Story $story, StoryService $service): RedirectResponse
    {
        $service->update(
            $story,
            $request->validated(),
            $request->file('media'),
        );

        return redirect()
            ->route('marketing.stories.index')
            ->with('success', 'Story updated successfully.');
    }

    public function destroy(Story $story, StoryService $service): RedirectResponse
    {
        $service->delete($story);

        return redirect()
            ->route('marketing.stories.index')
            ->with('success', 'Story removed.');
    }
}
