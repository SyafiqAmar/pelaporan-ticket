<?php

namespace App\Http\Controllers\Api;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\TicketResource as TicketApiResource;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Ticket::class);

        $tickets = Ticket::query()
            ->visibleTo($request->user())
            ->latest()
            ->paginate(15);

        return TicketApiResource::collection($tickets);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Ticket::class);

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'in:low,medium,high'],
        ]);

        $ticket = new Ticket($data);
        $ticket->user_id = $request->user()->id;
        $ticket->status = TicketStatus::OPEN;
        $ticket->save();

        return (new TicketApiResource($ticket))->response()->setStatusCode(201);
    }

    public function show(Request $request, Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        return new TicketApiResource($ticket);
    }

    public function update(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $isAdmin = $request->user()->hasRole('admin');

        $data = $request->validate([
            'subject' => [$isAdmin ? 'sometimes' : 'prohibited', 'string', 'max:255'],
            'description' => [$isAdmin ? 'sometimes' : 'prohibited', 'string'],
            'category' => [$isAdmin ? 'sometimes' : 'prohibited', 'string', 'max:255'],
            'priority' => [$isAdmin ? 'sometimes' : 'prohibited', 'in:low,medium,high'],
            'status' => ['sometimes', 'in:open,in_progress,resolved,closed'],
            'assigned_to' => [$isAdmin ? 'sometimes' : 'prohibited', 'nullable', 'exists:users,id'],
        ]);

        $ticket->fill($data);
        $ticket->save();

        return new TicketApiResource($ticket);
    }

    public function destroy(Request $request, Ticket $ticket)
    {
        $this->authorize('delete', $ticket);

        $ticket->delete();

        return response()->json(null, 204);
    }
}
