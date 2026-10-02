<?php

namespace Tests\Feature;

use App\Enums\SkillStatus;
use App\Livewire\Students\Show;
use App\Models\Course;
use App\Models\Sample;
use App\Models\Student;
use App\Models\StudentSkill;
use App\Models\User;
use Database\Seeders\CurriculumSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StudentProfileTest extends TestCase
{
    use RefreshDatabase;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CurriculumSeeder::class);
        $this->actingAs(User::factory()->create());
        $this->student = Student::factory()->create(['name' => 'Ira Menon']);
    }

    public function test_profile_tabs_render(): void
    {
        foreach (['overview', 'progress', 'samples'] as $tab) {
            $this->get(route('students.show', [$this->student, 'tab' => $tab]))->assertOk()->assertSee('Ira Menon');
        }
    }

    public function test_tapping_a_skill_cycles_its_status(): void
    {
        $skill = Course::firstWhere('name', 'Phonics')->levels->first()->skills->first();

        $component = Livewire::test(Show::class, ['student' => $this->student]);

        $expected = [SkillStatus::Practising, SkillStatus::Mastered, SkillStatus::NotStarted];
        foreach ($expected as $status) {
            $component->call('cycleSkill', $skill->id);
            $this->assertSame($status, StudentSkill::sole()->status);
        }
    }

    public function test_teacher_can_upload_and_delete_a_handwriting_photo(): void
    {
        Storage::fake('local');

        Livewire::test(Show::class, ['student' => $this->student])
            ->set('tab', 'samples')
            ->set('photo', UploadedFile::fake()->image('page.jpg', 3000, 4000))
            ->set('caption', 'Letters a to e')
            ->call('savePhoto')
            ->assertHasNoErrors();

        $sample = Sample::sole();
        Storage::disk('local')->assertExists([$sample->image_path, $sample->thumbPath()]);
        [$width, $height] = getimagesizefromstring(Storage::disk('local')->get($sample->image_path));
        $this->assertSame([1200, 1600], [$width, $height]);

        $this->get($sample->url(true))->assertOk()->assertHeader('Content-Type', 'image/jpeg');

        Livewire::test(Show::class, ['student' => $this->student])->call('deletePhoto', $sample->id);

        $this->assertSame(0, Sample::count());
        Storage::disk('local')->assertMissing($sample->image_path);
    }

    public function test_only_images_are_accepted(): void
    {
        Storage::fake('local');

        Livewire::test(Show::class, ['student' => $this->student])
            ->set('photo', UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'))
            ->call('savePhoto')
            ->assertHasErrors('photo');
    }

    public function test_photos_need_sign_in(): void
    {
        $sample = $this->student->samples()->create(['date' => now(), 'image_path' => 'samples/x.jpg']);

        auth()->logout();

        $this->get($sample->url())->assertRedirect('/login');
    }
}
