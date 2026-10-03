<?php

namespace Tests\Feature;

use App\Livewire\Students\Show;
use App\Models\Attendance;
use App\Models\Batch;
use App\Models\ClassSession;
use App\Models\Course;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\User;
use App\Services\Backup;
use App\Services\Billing;
use Database\Seeders\CurriculumSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ReportsBackupsTest extends TestCase
{
    use RefreshDatabase;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CurriculumSeeder::class);
        $this->actingAs(User::factory()->create());

        $this->student = Student::factory()->create(['name' => 'Zara Khan', 'dob' => now()->addDays(2)->subYears(7)]);
        $batch = Batch::create(['course_id' => Course::first()->id, 'name' => 'Writers A']);
        $session = ClassSession::create(['batch_id' => $batch->id, 'date' => now()->toDateString(), 'status' => 'held']);
        Attendance::create(['class_session_id' => $session->id, 'student_id' => $this->student->id, 'status' => 'present']);
        $invoice = Invoice::create(['student_id' => $this->student->id, 'period_label' => 'Workbook', 'due_date' => now(), 'amount' => 500]);
        app(Billing::class)->recordPayment($invoice, 500, now()->toDateString(), 'cash');
    }

    public function test_dashboard_shows_month_figures_and_birthdays(): void
    {
        $this->get('/')->assertOk()->assertSee('100%')->assertSee('₹500')->assertSee('Birthdays this week')->assertSee('turns 7');
    }

    public function test_reports_page_and_csv_exports(): void
    {
        $this->get('/reports')->assertOk()->assertSee('Writers A');

        foreach (['students', 'attendance', 'payments', 'outstanding'] as $type) {
            $response = $this->get(route('exports', ['type' => $type]));
            $response->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
            if ($type !== 'outstanding') {
                $this->assertStringContainsString('Zara Khan', $response->streamedContent());
            }
        }

        $this->get(route('exports', ['type' => 'nope']))->assertNotFound();
    }

    public function test_monthly_assessment_is_saved_with_scores(): void
    {
        $level = Course::firstWhere('name', 'Phonics')->levels->first();

        Livewire::test(Show::class, ['student' => $this->student])
            ->set('tab', 'progress')
            ->set('courseId', $level->course_id)
            ->call('startAssessment', $level->id)
            ->set('scores.'.$level->skills->first()->id, 5)
            ->set('assessmentNote', 'Great blending')
            ->call('saveAssessment')
            ->assertHasNoErrors()
            ->assertSee('Great blending');

        $assessment = $this->student->assessments()->with('scores')->sole();
        $this->assertCount($level->skills->count(), $assessment->scores);
        $this->assertSame(5, (int) $assessment->scores->firstWhere('skill_id', $level->skills->first()->id)->score);
    }

    public function test_backup_and_restore_round_trip(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('samples/1/a.jpg', 'photo-bytes');

        $backup = app(Backup::class);
        $file = $backup->create();
        $this->assertCount(1, $backup->list());

        $this->get(route('backups.download', basename($file)))->assertOk();
        $this->get(route('backups.download', '../../.env'))->assertNotFound();

        // Lose data, then restore it.
        $this->student->update(['name' => 'Changed']);
        Storage::disk('local')->delete('samples/1/a.jpg');

        $backup->restore(Storage::disk('local')->path($file));

        $this->assertSame('Zara Khan', Student::sole()->name);
        $this->assertSame(1, Attendance::count());
        $this->assertSame('photo-bytes', Storage::disk('local')->get('samples/1/a.jpg'));
    }

    public function test_backup_command(): void
    {
        Storage::fake('local');

        $this->artisan('backup:run')->assertSuccessful();

        $this->assertCount(1, app(Backup::class)->list());
    }
}
