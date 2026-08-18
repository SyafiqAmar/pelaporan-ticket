<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'subject' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'category' => fake()->word(),
            'priority' => fake()->randomElement(TicketPriority::cases())->value,
            'status' => TicketStatus::OPEN->value,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Ticket $ticket) {
            $ticket->user_id ??= User::factory()->create()->id;
        });
    }
}
