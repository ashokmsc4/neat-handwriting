<?php

namespace Tests\Feature;

use App\Livewire\Registrations\Index;
use App\Livewire\Registrations\Join;
use App\Models\Course;
use App\Models\Guardian;
use App\Models\Registration;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\CurriculumSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function fillForm($component)
    {
        return $component
            ->set('child_name', 'Ira Menon')
            ->set('dob', now()->subYears(6)->toDateString())
            ->set('grade', 'UKG')
            ->set('guardian_name', 'Anita Menon')
            ->set('phone', '98765 43210')
            ->set('photo_consent', true);
    }

    public function test_parent_can_register_without_signing_in(): void
    {
        $this->seed(CurriculumSeeder::class);
        $phonics = Course::firstWhere('name', 'Phonics');
        $token = Registration::token();

        $this->get('/join/'.$token)->assertOk()->assertSee('Student registration')->assertSee('Phonics');
        $this->get('/join/wrong-token')->assertNotFound();

        $this->fillForm(Livewire::test(Join::class, ['token' => $token]))
            ->set('course_ids', [(string) $phonics->id])
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('sent', true)
            ->assertSee('Thank you');

        $registration = Registration::sole();
        $this->assertSame('new', $registration->status);
        $this->assertSame([$phonics->id], $registration->course_ids);
        $this->assertSame(0, Student::count());
    }

    public function test_required_fields_and_closed_link(): void
    {
        $token = Registration::token();

        Livewire::test(Join::class, ['token' => $token])
            ->call('submit')
            ->assertHasErrors(['child_name', 'dob', 'guardian_name', 'phone']);

        Setting::put(['registration_open' => '0']);
        $this->get('/join/'.$token)->assertOk()->assertSee('Registrations are closed');
    }

    public function test_bots_filling_the_hidden_field_are_ignored(): void
    {
        $this->fillForm(Livewire::test(Join::class, ['token' => Registration::token()]))
            ->set('website', 'http://spam.example')
            ->call('submit')
            ->assertSet('sent', true);

        $this->assertSame(0, Registration::count());
    }

    public function test_teacher_accepts_a_registration_into_a_student(): void
    {
        $this->actingAs(User::factory()->create());
        $this->fillForm(Livewire::test(Join::class, ['token' => Registration::token()]))->call('submit');
        $sibling = Guardian::create(['name' => 'Anita Menon', 'phone' => '98765 43210']);
        $registration = Registration::sole();

        $this->get('/')->assertSee('1 new registration');
        $this->get('/registrations')->assertOk()->assertSee('Ira Menon');

        Livewire::test(Index::class)
            ->call('accept', $registration->id)
            ->assertRedirect(route('students.edit', Student::sole()));

        $student = Student::sole();
        $this->assertSame('Ira Menon', $student->name);
        $this->assertSame($sibling->id, $student->guardian_id);
        $this->assertStringContainsString('Photo consent: yes', $student->notes);
        $this->assertSame('accepted', $registration->fresh()->status);
        $this->assertSame($student->id, $registration->fresh()->student_id);
    }

    public function test_teacher_can_decline_and_make_a_new_link(): void
    {
        $this->actingAs(User::factory()->create());
        $old = Registration::token();
        $this->fillForm(Livewire::test(Join::class, ['token' => $old]))->call('submit');

        Livewire::test(Index::class)
            ->call('decline', Registration::sole()->id)
            ->call('newLink');

        $this->assertSame('declined', Registration::sole()->status);
        $this->assertNotSame($old, Registration::token());
        $this->get('/join/'.$old)->assertNotFound();
        $this->get('/join/'.Registration::token())->assertOk();
    }

    public function test_review_page_needs_sign_in(): void
    {
        $this->get('/registrations')->assertRedirect('/login');
    }
}
