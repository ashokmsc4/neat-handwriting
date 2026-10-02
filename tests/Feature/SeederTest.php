<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeding_creates_owner_and_curriculum_and_can_run_twice(): void
    {
        config(['school.owner.email' => 'teacher@example.com', 'school.owner.password' => 'pass-1234']);

        $this->seed();
        $this->seed();

        $this->assertSame(1, User::where('email', 'teacher@example.com')->count());
        $this->assertSame(3, Course::count());
        $this->assertTrue(Course::firstWhere('name', 'Phonics')->levels()->exists());
    }
}
