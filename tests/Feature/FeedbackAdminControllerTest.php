<?php

namespace Tests\Feature;

use App\Models\Feedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedbackAdminControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_feedback_without_exposing_admin_store_route(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $feedback = Feedback::create([
            'name' => 'Анастасия',
            'phone' => '+79990000000',
            'message' => 'Нужна передержка',
            'status' => 'new',
            'order' => 1,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.feedbacks.index'))
            ->assertOk()
            ->assertSee('Анастасия');

        $this->actingAs($admin)
            ->put(route('admin.feedbacks.update', $feedback), [
                'name' => 'Анастасия',
                'phone' => '+79990000000',
                'message' => 'Нужна передержка',
                'status' => 'in_progress',
            ])
            ->assertRedirect(route('admin.feedbacks.index'));

        $this->assertDatabaseHas('feedback', ['id' => $feedback->id, 'status' => 'in_progress']);
        $this->assertFalse(
            collect(app('router')->getRoutes()->getRoutes())
                ->contains(fn ($route) => $route->getName() === 'admin.feedbacks.store')
        );
    }
}
