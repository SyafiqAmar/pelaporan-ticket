<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\Tickets\TicketResource;   // file kamu sendiri
use Filament\Resources\Pages\CreateRecord;           // dari package Filament


class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $ticket = new Ticket($data); 
        $ticket->user_id = Auth::id();
        $ticket->status  = TicketStatus::OPEN;
        $ticket->save();

        return $ticket;
     }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
