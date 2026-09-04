<?php

namespace Modules\Support\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Support\Enums\TicketStatus;
use Modules\Support\Services\TicketService;

class UnassignedTicketController extends Controller
{
    public function index(Request $request, TicketService $tickets): Response
    {
        return Inertia::render('Support/Tickets/Index', [
            'tickets' => $tickets->listPaginated(
                $request->string('search')->trim()->toString() ?: null,
                $request->filled('status') ? TicketStatus::tryFrom($request->string('status')->toString()) : null,
                $request->integer('per_page', 25),
                unassignedOnly: true,
            ),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString(),
                'per_page' => $request->integer('per_page', 25),
            ],
            'statuses' => collect(TicketStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ])->all(),
            'pageTitle' => 'Unassigned Tickets',
            'listRoute' => 'support.unassigned',
            'allowCreate' => false,
        ]);
    }
}
