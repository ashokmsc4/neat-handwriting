<?php

namespace App\Livewire\Students;

use App\Enums\StudentStatus;
use App\Models\Batch;
use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Support\Carbon;
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

    /** @var array<int, string> */
    public array $batch_ids = [];

    public function mount(?Student $student = null): void
    {
        if (! $student?->exists) {
            $this->joined_on = now()->toDateString();

            return;
        }

        $this->batch_ids = $student->enrollments()->where('status', 'active')
            ->pluck('batch_id')->map(fn ($id) => (string) $id)->all();

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
            'batch_ids' => 'array',
            'batch_ids.*' => 'integer|exists:batches,id',
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

            $this->syncBatches(array_map('intval', $data['batch_ids'] ?? []));
        });

        session()->flash('status', 'Saved '.$this->name.'.');

        return $this->redirectRoute('students.show', $this->student, navigate: true);
    }

    /**
     * Enroll the student in newly ticked batches and end enrollments for unticked ones,
     * the same way the batch screen does.
     */
    private function syncBatches(array $batchIds): void
    {
        $today = Carbon::today();
        $enrollments = $this->student->enrollments();
        $current = (clone $enrollments)->where('status', 'active')->pluck('batch_id')->all();

        (clone $enrollments)->where('status', 'active')
            ->whereNotIn('batch_id', $batchIds)
            ->update(['status' => 'ended', 'end_date' => $today]);

        foreach (Batch::whereIn('id', array_diff($batchIds, $current))->get() as $batch) {
            Enrollment::create([
                'student_id' => $this->student->id,
                'batch_id' => $batch->id,
                'fee_plan_id' => $batch->fee_plan_id,
                'start_date' => $today,
                'status' => 'active',
            ]);
        }
    }

    public function render()
    {
        return view('livewire.students.form', [
            'statuses' => StudentStatus::cases(),
            // Stopped batches stay listed only if the student is still in them.
            'batches' => Batch::with(['course', 'schedules'])->withCount('students')
                ->where(fn ($q) => $q->where('active', true)->orWhereIn('id', $this->batch_ids))
                ->orderBy('name')->get(),
            'days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        ]);
    }
}
