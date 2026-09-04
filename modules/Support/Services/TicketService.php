<?php

namespace Modules\Support\Services;

use App\Core\Support\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Support\Enums\TicketPriority;
use Modules\Support\Enums\TicketStatus;
use Modules\Support\Models\SupportTicket;

class TicketService extends Service
{
    /**
     * @return array{open: int, in_progress: int, resolved: int, total: int}
     */
    public function overviewStats(): array
    {
        return [
            'open' => SupportTicket::query()->where('status', TicketStatus::Open)->count(),
            'in_progress' => SupportTicket::query()->where('status', TicketStatus::InProgress)->count(),
            'resolved' => SupportTicket::query()->where('status', TicketStatus::Resolved)->count(),
            'total' => SupportTicket::query()->count(),
        ];
    }

    public function listPaginated(
        ?string $search = null,
        ?TicketStatus $status = null,
        int $perPage = 25,
        ?int $createdBy = null,
        bool $unassignedOnly = false,
    ): LengthAwarePaginator {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        return SupportTicket::query()
            ->when($status, fn ($query, $status) => $query->where('status', $status->value))
            ->when($createdBy, fn ($query, $createdBy) => $query->where('created_by', $createdBy))
            ->when($unassignedOnly, fn ($query) => $query->whereNull('assigned_to'))
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                $inner->where('number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('requester_name', 'like', "%{$search}%")
                    ->orWhere('requester_email', 'like', "%{$search}%");
            }))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (SupportTicket $ticket) => $this->format($ticket));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $userId = null): SupportTicket
    {
        return SupportTicket::query()->create([
            'number' => $this->nextNumber(),
            'subject' => $data['subject'],
            'body' => $data['body'] ?? null,
            'status' => TicketStatus::Open,
            'priority' => $data['priority'] ?? TicketPriority::Normal->value,
            'requester_name' => $data['requester_name'] ?? null,
            'requester_email' => $data['requester_email'] ?? null,
            'created_by' => $userId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SupportTicket $ticket, array $data): SupportTicket
    {
        $status = isset($data['status'])
            ? TicketStatus::from($data['status'])
            : $ticket->status;

        $ticket->update([
            'subject' => $data['subject'] ?? $ticket->subject,
            'body' => $data['body'] ?? $ticket->body,
            'status' => $status,
            'priority' => $data['priority'] ?? $ticket->priority,
            'requester_name' => $data['requester_name'] ?? $ticket->requester_name,
            'requester_email' => $data['requester_email'] ?? $ticket->requester_email,
            'resolved_at' => in_array($status, [TicketStatus::Resolved, TicketStatus::Closed], true)
                ? ($ticket->resolved_at ?? now())
                : null,
        ]);

        return $ticket->fresh();
    }

    public function delete(SupportTicket $ticket): void
    {
        $ticket->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function format(SupportTicket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'number' => $ticket->number,
            'subject' => $ticket->subject,
            'body' => $ticket->body,
            'status' => $ticket->status?->value,
            'status_label' => $ticket->status?->label(),
            'priority' => $ticket->priority?->value,
            'priority_label' => $ticket->priority?->label(),
            'requester_name' => $ticket->requester_name,
            'requester_email' => $ticket->requester_email,
            'resolved_at' => $ticket->resolved_at?->toIso8601String(),
            'created_at' => $ticket->created_at?->toIso8601String(),
        ];
    }

    private function nextNumber(): string
    {
        $n = SupportTicket::query()->count() + 1;

        return 'TKT-'.str_pad((string) $n, 5, '0', STR_PAD_LEFT);
    }
}
