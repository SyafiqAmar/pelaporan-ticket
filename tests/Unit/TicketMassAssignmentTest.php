<?php

namespace Tests\Unit;

use App\Models\Ticket;
use Tests\TestCase;

class TicketMassAssignmentTest extends TestCase
{
    public function test_user_id_cannot_be_set_via_mass_assignment(): void
    {
        $ticket = new Ticket([
            'subject' => 'Laptop rusak',
            'user_id' => 999,
        ]);

        $this->assertSame('Laptop rusak', $ticket->subject);
        $this->assertNull($ticket->user_id);
    }
}
