<?php

namespace App\Livewire\Registrations;

use App\Models\Course;
use App\Models\Guardian;
use App\Models\Registration;
use App\Models\Setting;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Registrations')]
class Index extends Component
{
    public function accept(int $id)
    {
        $registration = Registration::pending()->findOrFail($id);

        $student = DB::transaction(function () use ($registration) {
            // Siblings share a parent: reuse the guardian with the same phone number.
            $guardian = Guardian::firstOrCreate(['phone' => $registration->phone], [
                'name' => $registration->guardian_name,
                'whatsapp' => $registration->whatsapp,
                'email' => $registration->email,
                'address' => $registration->address,
            ]);

            $notes = array_filter([
                $registration->notes,
                ($courses = $registration->courses()->pluck('name')->join(', ')) ? 'Interested in: '.$courses : null,
                $registration->preferred_time ? 'Preferred time: '.$registration->preferred_time : null,
                'Photo consent: '.($registration->photo_consent ? 'yes' : 'no'),
            ]);

            $student = Student::create([
                'guardian_id' => $guardian->id,
                'name' => $registration->child_name,
                'dob' => $registration->dob,
                'school' => $registration->school,
                'grade' => $registration->grade,
                'status' => 'active',
                'joined_on' => now()->toDateString(),
                'notes' => implode("\n", $notes),
            ]);

            $registration->update(['status' => 'accepted', 'student_id' => $student->id]);

            return $student;
        });

        session()->flash('status', 'Added '.$student->name.'. Pick their batch and save.');

        // Straight to the student form so the batch can be chosen.
        return $this->redirectRoute('students.edit', $student, navigate: true);
    }

    public function decline(int $id): void
    {
        Registration::pending()->findOrFail($id)->update(['status' => 'declined']);
    }

    public function restore(int $id): void
    {
        Registration::where('status', 'declined')->findOrFail($id)->update(['status' => 'new']);
    }

    public function toggleOpen(): void
    {
        Setting::put(['registration_open' => Registration::isOpen() ? '0' : '1']);
    }

    public function newLink(): void
    {
        Registration::newToken();
    }

    public function render()
    {
        $link = route('register', Registration::token());
        $school = Setting::get('school_name');

        return view('livewire.registrations.index', [
            'pending' => Registration::pending()->latest()->get(),
            'handled' => Registration::where('status', '!=', 'new')->with('student')->latest('updated_at')->take(20)->get(),
            'courseNames' => Course::pluck('name', 'id'),
            'open' => Registration::isOpen(),
            'link' => $link,
            'shareText' => "Hello! Please register your child for {$school} using this form: {$link}",
        ]);
    }
}
