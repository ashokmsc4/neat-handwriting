<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Livewire\Attendance\Take;
use App\Models\Attendance;
use App\Models\Batch;
use App\Models\ClassSession;
use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private Batch $batch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());

        $this->batch = Batch::create(['course_id' => Course::create(['name' => 'Phonics'])->id, 'name' => 'Sounds A']);
        $this->batch->schedules()->create(['weekday' => now()->dayOfWeek, 'start_time' => '16:00:00', 'end_time' => '17:00:00']);
        $this->batch->students()->attach(
            Student::factory()->count(3)->create()->pluck('id'),
            ['start_date' => now(), 'status' => 'active'],
        );
    }

    public function test_today_links_to_attendance(): void
    {
        $this->get('/')->assertOk()->assertSee('Sounds A')->assertSee('Take attendance');
        $this->get(route('attendance.take', $this->batch))->assertOk();
    }

    public function test_everyone_starts_present_and_teacher_marks_absences(): void
    {
        [$a, $b, $c] = $this->batch->students()->orderBy('name')->get();

        Livewire::test(Take::class, ['batch' => $this->batch])
            ->assertSet("marks.{$a->id}", 'present')
            ->set("marks.{$b->id}", 'absent')
            ->set('topicNote', 'Sounds s a t')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $session = ClassSession::sole();
        $this->assertSame('held', $session->status);
        $this->assertSame('Sounds s a t', $session->topic_note);
        $this->assertSame(AttendanceStatus::Absent, Attendance::firstWhere('student_id', $b->id)->status);
        $this->assertSame(2, Attendance::where('status', 'present')->count());

        $this->get('/')->assertSee('Done ✓');
    }

    public function test_saving_twice_updates_instead_of_duplicating(): void
    {
        $student = $this->batch->students()->first();

        Livewire::test(Take::class, ['batch' => $this->batch])->call('save');

        Livewire::test(Take::class, ['batch' => $this->batch])
            ->assertSet('alreadySaved', true)
            ->set("marks.{$student->id}", 'late')
            ->call('save');

        $this->assertSame(1, ClassSession::count());
        $this->assertSame(3, Attendance::count());
        $this->assertSame(AttendanceStatus::Late, Attendance::firstWhere('student_id', $student->id)->status);
    }

    public function test_attendance_can_be_taken_for_a_past_date_but_not_the_future(): void
    {
        $this->get(route('attendance.take', [$this->batch, now()->subDays(3)->toDateString()]))->assertOk();
        $this->get(route('attendance.take', [$this->batch, now()->addDay()->toDateString()]))->assertNotFound();
    }
}
