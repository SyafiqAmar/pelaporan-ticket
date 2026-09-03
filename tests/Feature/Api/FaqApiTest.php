<?php

namespace Tests\Feature\Api;

use App\Models\Faq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FaqApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'user']);
    }

    private function makeUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_guest_can_list_faqs_via_api(): void
    {
        Faq::factory()->count(2)->create();

        $this->getJson('/api/faqs')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_guest_can_view_single_faq_via_api(): void
    {
        $faq = Faq::factory()->create();

        $response = $this->getJson("/api/faqs/{$faq->id}")->assertOk();

        $body = $response->json();
        $question = $body['question'] ?? $body['data']['question'] ?? null;

        $this->assertSame($faq->question, $question);
    }

    public function test_guest_cannot_create_faq_via_api(): void
    {
        $this->postJson('/api/faqs', [
            'question' => 'Test',
            'answer' => '<p>Test</p>',
        ])->assertForbidden();
    }

    public function test_admin_can_create_faq_via_api(): void
    {
        $admin = $this->makeUser('admin');
        Sanctum::actingAs($admin, ['*']);

        $response = $this->postJson('/api/faqs', [
            'question' => 'Bagaimana cara reset password?',
            'answer' => '<p>Klik Forgot Password.</p>',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('faqs', ['question' => 'Bagaimana cara reset password?']);
    }

    public function test_non_admin_cannot_create_faq_via_api(): void
    {
        $user = $this->makeUser('user');
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/faqs', [
            'question' => 'Test',
            'answer' => '<p>Test</p>',
        ])->assertForbidden();
    }

    public function test_admin_can_update_faq_via_api(): void
    {
        $admin = $this->makeUser('admin');
        $faq = Faq::factory()->create();
        Sanctum::actingAs($admin, ['*']);

        $this->putJson("/api/faqs/{$faq->id}", [
            'answer' => '<p>Jawaban baru</p>',
        ])->assertOk();

        $this->assertDatabaseHas('faqs', ['id' => $faq->id, 'answer' => '<p>Jawaban baru</p>']);
    }

    public function test_admin_can_delete_faq_via_api(): void
    {
        $admin = $this->makeUser('admin');
        $faq = Faq::factory()->create();
        Sanctum::actingAs($admin, ['*']);

        $this->deleteJson("/api/faqs/{$faq->id}")->assertOk();
        $this->assertDatabaseMissing('faqs', ['id' => $faq->id]);
    }
}