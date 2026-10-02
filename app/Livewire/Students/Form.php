<?php

namespace App\Livewire\Students;

use App\Enums\StudentStatus;
use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Student')]
class Form extends Component
{
    public ?Student $student = null;

    public string $name = '';

    public ?string $dob = null;

    public string $school = '';

    public string $grade = '';

    public string $status = 'active';

    public ?string $joined_on = null;

    public string $notes = '';

    public string $guardian_name = '';

    public string $guardian_phone = '';

    public string $guardian_whatsapp = '';

    public string $guardian_email = '';

    public function mount(?Student $student = null): void
    {
        if (! $student?->exists) {
            $this->joined_on = now()->toDateString();

            return;
        }

        $this->student = $student;
        $this->fill([
            'name' => $student->name,
            'dob' => $student->dob?->toDateString(),
            'school' => (string) $student->school,
            'grade' => (string) $student->grade,
            'status' => $student->status->value,
            'joined_on' => $student->joined_on?->toDateString(),
            'notes' => (string) $student->notes,
            'guardian_name' => (string) $student->guardian?->name,
            'guardian_phone' => (string) $student->guardian?->phone,
            'guardian_whatsapp' => (string) $student->guardian?->whatsapp,
            'guardian_email' => (string) $student->guardian?->email,
        ]);
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'dob' => 'nullable|date|before:today',
            'school' => 'nullable|string|max:255',
            'grade' => 'nullable|string|max:30',
            'status' => ['required', Rule::enum(StudentStatus::class)],
            'joined_on' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
            'guardian_name' => 'required|string|max:255',
            'guardian_phone' => 'required|string|max:20',
            'guardian_whatsapp' => 'nullable|string|max:20',
            'guardian_email' => 'nullable|email|max:255',
        ];
    }

    public function save()
    {
        $data = $this->validate();

        DB::transaction(function () use ($data) {
            $guardianData = [
                'name' => $data['guardian_name'],
                'phone' => $data['guardian_phone'],
                'whatsapp' => $data['guardian_whatsapp'] ?: null,
                'email' => $data['guardian_email'] ?: null,
            ];

            $guardian = $this->student?->guardian;

            if ($guardian) {
                $guardian->update($guardianData);
            } else {
                // Siblings share a parent: reuse the guardian with the same phone number.
                $guardian = Guardian::firstOrCreate(['phone' => $guardianData['phone']], $guardianData);
            }

            $studentData = [
                'guardian_id' => $guardian->id,
                'name' => $data['name'],
                'dob' => $data['dob'] ?: null,
                'school' => $data['school'] ?: null,
                'grade' => $data['grade'] ?: null,
                'status' => $data['status'],
                'joined_on' => $data['joined_on'] ?: null,
                'notes' => $data['notes'] ?: null,
            ];

            $this->student
                ? $this->student->update($studentData)
                : $this->student = Student::create($studentData);
        });

        session()->flash('status', 'Saved '.$this->name.'.');

        return $this->redirectRoute('students.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.students.form', [
            'statuses' => StudentStatus::cases(),
        ]);
    }
}
