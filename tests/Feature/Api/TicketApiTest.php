<?php

namespace Tests\Feature\Api;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TicketApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);
        $staffItRole = Role::firstOrCreate(['name' => 'staff_it']);

        foreach (['View:Ticket', 'ViewAny:Ticket', 'Create:Ticket', 'Update:Ticket'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $userRole->givePermissionTo(['View:Ticket', 'ViewAny:Ticket', 'Create:Ticket']);
        $staffItRole->givePermissionTo(['View:Ticket', 'ViewAny:Ticket', 'Update:Ticket']);
    }

    private function makeUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function ticketOwnedBy(User $owner): Ticket
    {
        $ticket = Ticket::factory()->create();
        $ticket->user_id = $owner->id;
        $ticket->save();

        return $ticket;
    }

    private function ticketAssignedTo(User $assignee): Ticket
    {
        $creator = User::factory()->create();

        $ticket = Ticket::factory()->create();
        $ticket->user_id = $creator->id;
        $ticket->assigned_to = $assignee->id;
        $ticket->save();

        return $ticket;
    }

    public function test_guest_cannot_access_tickets_endpoint(): void
    {
        $this->getJson('/api/tickets')->assertUnauthorized();
    }

    public function test_user_can_create_ticket_via_api(): void
    {
        $user = $this->makeUser('user');
        Sanctum::actingAs($user, ['*'], 'api');

        $response = $this->postJson('/api/tickets', [
            'subject' => 'Printer rusak',
            'description' => 'Tidak bisa print',
            'category' => 'hardware',
            'priority' => 'medium',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('tickets', [
            'subject' => 'Printer rusak',
            'user_id' => $user->id,
            'status' => 'open',
        ]);
    }

    public function test_creating_ticket_without_required_fields_fails_validation(): void
    {
        $user = $this->makeUser('user');
        Sanctum::actingAs($user, ['*'], 'api');

        $this->postJson('/api/tickets', [])->assertStatus(422);
    }

    public function test_user_can_only_see_own_tickets_via_api(): void
    {
        $owner = $this->makeUser('user');
        $other = $this->makeUser('user');

        $ownTicket = $this->ticketOwnedBy($owner);
        $this->ticketOwnedBy($other);

        Sanctum::actingAs($owner, ['*'], 'api');

        $this->getJson('/api/tickets')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownTicket->id);
    }

    public function test_staff_it_can_only_see_assigned_tickets_via_api(): void
    {
        $staffIt = $this->makeUser('staff_it');
        $otherStaffIt = $this->makeUser('staff_it');

        $assignedTicket = $this->ticketAssignedTo($staffIt);
        $this->ticketAssignedTo($otherStaffIt);

        Sanctum::actingAs($staffIt, ['*'], 'api');

        $this->getJson('/api/tickets')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $assignedTicket->id);
    }

    public function test_admin_sees_all_tickets_via_api(): void
    {
        $admin = $this->makeUser('admin');
        $this->ticketOwnedBy($this->makeUser('user'));
        $this->ticketOwnedBy($this->makeUser('user'));

        Sanctum::actingAs($admin, ['*'], 'api');

        $this->getJson('/api/tickets')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_user_cannot_view_ticket_owned_by_another_user_via_api(): void
    {
        $owner = $this->makeUser('user');
        $intruder = $this->makeUser('user');
        $ticket = $this->ticketOwnedBy($owner);

        Sanctum::actingAs($intruder, ['*'], 'api');

        $this->getJson("/api/tickets/{$ticket->id}")->assertNotFound();
    }

    public function test_staff_it_cannot_update_non_status_fields_via_api(): void
    {
        $staffIt = $this->makeUser('staff_it');
        $ticket = $this->ticketAssignedTo($staffIt);

        Sanctum::actingAs($staffIt, ['*'], 'api');

        $this->putJson("/api/tickets/{$ticket->id}", [
            'subject' => 'Coba ubah subject',
        ])->assertStatus(422);
    }

    public function test_staff_it_can_update_status_of_assigned_ticket_via_api(): void
    {
        $staffIt = $this->makeUser('staff_it');
        $ticket = $this->ticketAssignedTo($staffIt);

        Sanctum::actingAs($staffIt, ['*'], 'api');

        $this->putJson("/api/tickets/{$ticket->id}", [
            'status' => 'in_progress',
        ])->assertOk();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_admin_can_delete_ticket_via_api(): void
    {
        $admin = $this->makeUser('admin');
        $ticket = $this->ticketOwnedBy($this->makeUser('user'));

        Sanctum::actingAs($admin, ['*'], 'api');

        $this->deleteJson("/api/tickets/{$ticket->id}")->assertNoContent();
        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
    }

    public function test_user_cannot_delete_ticket_via_api(): void
    {
        $owner = $this->makeUser('user');
        $ticket = $this->ticketOwnedBy($owner);

        Sanctum::actingAs($owner, ['*'], 'api');

        $this->deleteJson("/api/tickets/{$ticket->id}")->assertForbidden();
    }

    public function test_admin_can_filter_tickets_by_status_via_search_endpoint(): void
    {
        $admin = $this->makeUser('admin');
        $open = $this->ticketOwnedBy($this->makeUser('user'));
        $resolved = $this->ticketOwnedBy($this->makeUser('user'));
        $resolved->status = 'resolved';
        $resolved->save();

        Sanctum::actingAs($admin, ['*'], 'api');

        $response = $this->postJson('/api/tickets/search', [
            'filters' => [
                ['field' => 'status', 'operator' => '=', 'value' => 'open'],
            ],
        ]);

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $open->id);
    }
}