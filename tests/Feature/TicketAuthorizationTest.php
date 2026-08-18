<?php

namespace Tests\Feature;

use App\Filament\Resources\Tickets\TicketResource;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TicketAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        foreach (['View:Ticket', 'ViewAny:Ticket', 'Create:Ticket'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $userRole->givePermissionTo(['View:Ticket', 'ViewAny:Ticket', 'Create:Ticket']);
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

    public function test_user_without_role_cannot_access_panel(): void
    {
        $user = User::factory()->create();

        $this->assertFalse(
            $user->canAccessPanel(\Filament\Facades\Filament::getPanel('admin'))
        );
    }

    public function test_user_with_role_can_access_panel(): void
    {
        $user = $this->makeUser('user');

        $this->assertTrue(
            $user->canAccessPanel(\Filament\Facades\Filament::getPanel('admin'))
        );
    }

    public function test_user_list_query_only_shows_own_tickets(): void
    {
        $owner = $this->makeUser('user');
        $other = $this->makeUser('user');

        $ownTicket = $this->ticketOwnedBy($owner);
        $this->ticketOwnedBy($other);

        $this->actingAs($owner);

        $visibleIds = TicketResource::getEloquentQuery()->pluck('id')->all();

        $this->assertSame([$ownTicket->id], $visibleIds);
    }

    public function test_admin_list_query_shows_all_tickets(): void
    {
        $admin = $this->makeUser('admin');
        $this->ticketOwnedBy($this->makeUser('user'));
        $this->ticketOwnedBy($this->makeUser('user'));

        $this->actingAs($admin);

        $this->assertCount(2, TicketResource::getEloquentQuery()->get());
    }

    public function test_user_cannot_view_ticket_owned_by_another_user(): void
    {
    $owner = $this->makeUser('user');
    $intruder = $this->makeUser('user');
    $ticket = $this->ticketOwnedBy($owner);

    $this->actingAs($intruder)
        ->get(TicketResource::getUrl('view', ['record' => $ticket]))
        ->assertNotFound();
    }

    public function test_user_cannot_edit_ticket_owned_by_another_user(): void
    {
        $owner = $this->makeUser('user');
        $intruder = $this->makeUser('user');
        $ticket = $this->ticketOwnedBy($owner);

        $this->actingAs($intruder)
            ->get(TicketResource::getUrl('edit', ['record' => $ticket]))
            ->assertNotFound();
    }


    public function test_admin_can_view_and_edit_any_ticket(): void
    {
        $admin = $this->makeUser('admin');
        $ticket = $this->ticketOwnedBy($this->makeUser('user'));

        $this->actingAs($admin)
            ->get(TicketResource::getUrl('view', ['record' => $ticket]))
            ->assertOk();

        $this->actingAs($admin)
            ->get(TicketResource::getUrl('edit', ['record' => $ticket]))
            ->assertOk();
    }
}
