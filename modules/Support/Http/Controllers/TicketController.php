<?php

namespace Modules\Support\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Support\Enums\TicketStatus;
use Modules\Support\Http\Requests\StoreTicketRequest;
use Modules\Support\Http\Requests\UpdateTicketRequest;
use Modules\Support\Models\SupportTicket;
use Modules\Support\Services\TicketService;

class TicketController extends Controller
{
    public function index(Request $request, TicketService $tickets): Response
    {
        $status = $request->filled('status')
            ? TicketStatus::tryFrom($request->string('status')->toString())
            : null;

        return Inertia::render('Support/Tickets/Index', [
            'tickets' => $tickets->listPaginated(
                $request->string('search')->trim()->toString() ?: null,
                $status,
                $request->integer('per_page', 25),
            ),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $status?->value,
                'per_page' => $request->integer('per_page', 25),
            ],
            'statuses' => collect(TicketStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ])->all(),
        ]);
    }

    public function store(StoreTicketRequest $request, TicketService $tickets): RedirectResponse
    {
        $tickets->create($request->validated(), $request->user()?->id);

        return back()->with('success', 'Ticket created.');
    }

    public function update(UpdateTicketRequest $request, SupportTicket $supportTicket, TicketService $tickets): RedirectResponse
    {
        $tickets->update($supportTicket, $request->validated());

        return back()->with('success', 'Ticket updated.');
    }

    public function destroy(SupportTicket $supportTicket, TicketService $tickets): RedirectResponse
    {
        $tickets->delete($supportTicket);

        return back()->with('success', 'Ticket deleted.');
    }
}
