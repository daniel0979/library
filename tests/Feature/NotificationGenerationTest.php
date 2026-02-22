<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registering_member_creates_welcome_and_admin_notifications(): void
    {
        $adminRole = Role::create(['name' => 'admin']);
        $memberRole = Role::create(['name' => 'member']);

        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);

        $response = $this->post(route('register.post'), [
            'name' => 'Jane Member',
            'email' => 'jane@example.com',
            'phone' => '123456789',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));

        $registeredUser = User::where('email', 'jane@example.com')->firstOrFail();
        $this->assertSame($memberRole->id, $registeredUser->role_id);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $registeredUser->id,
            'type' => 'general',
            'message' => '[Welcome] Your account is ready. You will now receive updates about new members and new books.',
            'status' => 'sent',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'type' => 'general',
            'message' => '[Community] New member joined: Jane Member (jane@example.com).',
            'status' => 'sent',
        ]);
    }

    public function test_adding_book_creates_new_book_notifications_for_active_users(): void
    {
        $adminRole = Role::create(['name' => 'admin']);
        $memberRole = Role::create(['name' => 'member']);

        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);

        $activeMember = User::factory()->create([
            'role_id' => $memberRole->id,
            'status' => 'active',
        ]);

        $inactiveMember = User::factory()->create([
            'role_id' => $memberRole->id,
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($admin)->postJson('/api/library/books', [
            'isbn' => '9780135957059',
            'title' => 'Clean Architecture',
            'author' => 'Robert C. Martin',
            'category' => 'Software',
            'total_copies' => 5,
            'shelf_location' => 'A-03',
            'description' => 'Practical software architecture principles.',
        ]);

        $response->assertCreated();

        $expectedMessage = '[Book] New book added: Clean Architecture by Robert C. Martin.';

        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'type' => 'general',
            'message' => $expectedMessage,
            'status' => 'sent',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $activeMember->id,
            'type' => 'general',
            'message' => $expectedMessage,
            'status' => 'sent',
        ]);

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $inactiveMember->id,
            'type' => 'general',
            'message' => $expectedMessage,
        ]);
    }
}
