<?php

namespace Tests\Feature;

use App\Enums\StudentStatus;
use App\Livewire\Students\Form;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_pages_render(): void
    {
        Student::factory()->create(['name' => 'Aarav Sharma']);

        $this->get('/')->assertOk();
        $this->get('/students')->assertOk()->assertSee('Aarav Sharma');
        $this->get('/students/new')->assertOk();
        $this->get('/batches')->assertOk();
        $this->get('/fees')->assertOk();
    }

    public function test_teacher_can_add_a_student_with_parent(): void
    {
        Livewire::test(Form::class)
            ->set('name', 'Diya Patel')
            ->set('grade', 'UKG')
            ->set('guardian_name', 'Meera Patel')
            ->set('guardian_phone', '98765 43210')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('students.index'));

        $student = Student::firstWhere('name', 'Diya Patel');
        $this->assertSame(StudentStatus::Active, $student->status);
        $this->assertSame('Meera Patel', $student->guardian->name);
        $this->assertSame('919876543210', $student->guardian->whatsappNumber());
    }

    public function test_siblings_share_one_parent_record(): void
    {
        foreach (['Kabir', 'Anya'] as $name) {
            Livewire::test(Form::class)
                ->set('name', $name)
                ->set('guardian_name', 'Ravi')
                ->set('guardian_phone', '9000000001')
                ->call('save');
        }

        $this->assertSame(1, Guardian::count());
        $this->assertSame(2, Guardian::first()->students()->count());
    }

    public function test_teacher_can_edit_a_student(): void
    {
        $student = Student::factory()->create();

        Livewire::test(Form::class, ['student' => $student])
            ->assertSet('name', $student->name)
            ->set('status', 'paused')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(StudentStatus::Paused, $student->fresh()->status);
    }

    public function test_name_and_parent_phone_are_required(): void
    {
        Livewire::test(Form::class)
            ->call('save')
            ->assertHasErrors(['name' => 'required', 'guardian_phone' => 'required']);
    }
}
