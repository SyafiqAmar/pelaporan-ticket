<?php

namespace App\Http\Controllers\Api;

use App\Models\Ticket;
use App\Enums\TicketStatus;
use App\Policies\TicketPolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Orion\Http\Controllers\Controller;

class TicketController extends Controller
{
    protected $model = Ticket::class;

    protected $policy = TicketPolicy::class;

    public function searchableBy(): array
    {
        return ['subject', 'category'];
    }

    public function filterableBy(): array
    {
        return ['subject', 'category', 'priority', 'status', 'created_at'];
    }

    protected function buildFetchQuery(Request $request, array $requestedRelations): Builder
    {
        return parent::buildFetchQuery($request, $requestedRelations)
            ->visibleTo($request->user());
    }

    protected function beforeSave(Request $request, Model $ticket): void
    {
        // if (! $ticket->exists) {
        //     $request->validate([
        //         'subject' => 'required|string|max:255',
        //         'description' => 'required|string',
        //         'category' => 'required|string|max:255',
        //         'priority' => 'required|in:low,medium,high',
        //     ]);

            $ticket->user_id = $request->user()->id;
            $ticket->status = TicketStatus::OPEN;

            return;
        }

        $request->validate([
            'status' => ['sometimes', 'in:open,in_progress,resolved,closed'],
        ]);
    }

    protected function beforeUpdate(Request $request, Model $ticket): void
    {
        if ($request->user()->hasRole('admin')) {
            return;
        }

        $forbidden = array_intersect(
            array_keys($request->all()),
            ['subject', 'description', 'category', 'priority', 'assigned_to']
        );

        abort_if($forbidden !== [], 422, 'Field ini hanya boleh diubah admin: ' . implode(', ', $forbidden));
    }
}
