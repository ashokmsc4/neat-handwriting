<?php

namespace Tests\Feature;

use App\Livewire\Batches\Form;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\CurriculumSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BatchesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CurriculumSeeder::class);
        $this->actingAs(User::factory()->create());
    }

    public function test_pages_render(): void
    {
        $this->get('/batches/new')->assertOk()->assertSee('Weekly schedule');
    }

    public function test_teacher_can_create_a_batch_with_schedule_and_students(): void
    {
        $course = Course::firstWhere('name', 'Phonics');
        [$a, $b] = Student::factory()->count(2)->create();

        Livewire::test(Form::class)
            ->set('name', 'Phonics Starters')
            ->set('course_id', $course->id)
            ->set('level_id', $course->levels->first()->id)
            ->set('schedules', [
                ['weekday' => 1, 'start_time' => '16:00', 'end_time' => '17:00'],
                ['weekday' => 3, 'start_time' => '16:00', 'end_time' => '17:00'],
            ])
            ->set('student_ids', [(string) $a->id, (string) $b->id])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('batches.index'));

        $batch = Batch::firstWhere('name', 'Phonics Starters');
        $this->assertEquals([1, 3], $batch->schedules->pluck('weekday')->all());
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $batch->students->pluck('id')->all());
    }

    public function test_removing_a_student_ends_their_enrollment(): void
    {
        $batch = Batch::create(['course_id' => Course::first()->id, 'name' => 'Cursive A']);
        [$a, $b] = Student::factory()->count(2)->create();
        $batch->students()->attach([$a->id, $b->id], ['start_date' => now(), 'status' => 'active']);

        Livewire::test(Form::class, ['batch' => $batch])
            ->assertSet('student_ids', [$a->id, $b->id])
            ->set('student_ids', [$a->id])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals([$a->id], $batch->fresh()->students->pluck('id')->all());
        $this->assertSame('ended', $batch->enrollments()->where('student_id', $b->id)->value('status'));
    }

    public function test_end_time_must_be_after_start_time(): void
    {
        Livewire::test(Form::class)
            ->set('name', 'Bad times')
            ->set('schedules', [['weekday' => 2, 'start_time' => '17:00', 'end_time' => '16:00']])
            ->call('save')
            ->assertHasErrors(['schedules.0.end_time']);
    }

    public function test_level_must_belong_to_the_course(): void
    {
        $print = Course::firstWhere('name', 'Handwriting (Print)');
        $phonicsLevel = Course::firstWhere('name', 'Phonics')->levels->first();

        Livewire::test(Form::class)
            ->set('name', 'Mismatch')
            ->set('course_id', $print->id)
            ->set('level_id', $phonicsLevel->id)
            ->call('save')
            ->assertHasErrors(['level_id']);
    }
}
