<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;

class Ticket extends Model
{

    #[Fillable([
        'subject',
        'description',
        'category',
        'priority',
        'attachment_path',
    ])]

    protected $casts = [
        'priority' => \App\Enums\TicketPriority::class,
        'status' => \App\Enums\TicketStatus::class,
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class, 'ticket_id');
    }
}
