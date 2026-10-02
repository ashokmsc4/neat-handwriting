<?php

namespace App\Livewire\Batches;

use App\Models\Batch;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Batch')]
class Form extends Component
{
    public ?Batch $batch = null;

    public string $name = '';

    public ?int $course_id = null;

    public ?int $level_id = null;

    public string $mode = 'offline';

    public string $meeting_link = '';

    public ?int $capacity = null;

    public ?string $start_date = null;

    public bool $active = true;

    /** @var array<int, array{weekday: int|string, start_time: string, end_time: string}> */
    public array $schedules = [];

    /** @var array<int, int|string> Ids of students in this batch. */
    public array $student_ids = [];

    public string $studentSearch = '';

    public function mount(?Batch $batch = null): void
    {
        if (! $batch?->exists) {
            $this->course_id = Course::where('active', true)->orderBy('name')->value('id');
            $this->start_date = now()->toDateString();
            $this->addSlot();

            return;
        }

        $this->batch = $batch;
        $this->fill([
            'name' => $batch->name,
            'course_id' => $batch->course_id,
            'level_id' => $batch->level_id,
            'mode' => $batch->mode ?? 'offline',
            'meeting_link' => (string) $batch->meeting_link,
            'capacity' => $batch->capacity,
            'start_date' => $batch->start_date?->toDateString(),
            'active' => $batch->active ?? true,
        ]);

        $this->schedules = $batch->schedules->map(fn ($slot) => [
            'weekday' => $slot->weekday,
            'start_time' => substr($slot->start_time, 0, 5),
            'end_time' => substr($slot->end_time, 0, 5),
        ])->all();

        $this->student_ids = $batch->enrollments()->where('status', 'active')->pluck('student_id')->all();
    }

    public function updatedCourseId(): void
    {
        $this->level_id = null;
    }

    public function addSlot(): void
    {
        $last = end($this->schedules) ?: ['weekday' => 1, 'start_time' => '16:00', 'end_time' => '17:00'];

        $this->schedules[] = [
            'weekday' => ((int) $last['weekday'] + 1) % 7,
            'start_time' => $last['start_time'],
            'end_time' => $last['end_time'],
        ];
    }

    public function removeSlot(int $index): void
    {
        unset($this->schedules[$index]);
        $this->schedules = array_values($this->schedules);
    }

    #[Computed]
    public function courses()
    {
        return Course::with('levels')->where('active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function students()
    {
        return Student::query()
            ->where(fn ($q) => $q->active()->orWhereIn('id', $this->student_ids))
            ->when($this->studentSearch !== '', fn ($q) => $q->where('name', 'like', '%'.$this->studentSearch.'%'))
            ->orderBy('name')
            ->get(['id', 'name', 'grade']);
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'course_id' => 'required|exists:courses,id',
            'level_id' => ['nullable', Rule::exists('levels', 'id')->where('course_id', $this->course_id)],
            'mode' => 'required|in:offline,online',
            'meeting_link' => 'nullable|url|max:255',
            'capacity' => 'nullable|integer|min:1|max:500',
            'start_date' => 'nullable|date',
            'active' => 'boolean',
            'schedules' => 'array',
            'schedules.*.weekday' => 'required|integer|between:0,6',
            'schedules.*.start_time' => 'required|date_format:H:i',
            'schedules.*.end_time' => 'required|date_format:H:i|after:schedules.*.start_time',
            'student_ids' => 'array',
            'student_ids.*' => 'integer|exists:students,id',
        ];
    }

    protected function messages(): array
    {
        return ['schedules.*.end_time.after' => 'End time must be after the start time.'];
    }

    public function save()
    {
        $data = $this->validate();

        DB::transaction(function () use ($data) {
            $batchData = [
                'name' => $data['name'],
                'course_id' => $data['course_id'],
                'level_id' => $data['level_id'] ?: null,
                'mode' => $data['mode'],
                'meeting_link' => $data['meeting_link'] ?: null,
                'capacity' => $data['capacity'] ?: null,
                'start_date' => $data['start_date'] ?: null,
                'active' => $data['active'],
            ];

            $this->batch
                ? $this->batch->update($batchData)
                : $this->batch = Batch::create($batchData);

            $this->batch->schedules()->delete();
            foreach ($data['schedules'] as $slot) {
                $this->batch->schedules()->create([
                    'weekday' => (int) $slot['weekday'],
                    'start_time' => $slot['start_time'].':00',
                    'end_time' => $slot['end_time'].':00',
                ]);
            }

            $this->syncStudents(array_map('intval', $data['student_ids']));
        });

        session()->flash('status', 'Saved '.$this->name.'.');

        return $this->redirectRoute('batches.index', navigate: true);
    }

    /** Enrolls newly ticked students and ends enrollments for students who were unticked. */
    private function syncStudents(array $studentIds): void
    {
        $today = Carbon::today();
        $current = $this->batch->enrollments()->where('status', 'active')->pluck('student_id')->all();

        $this->batch->enrollments()
            ->where('status', 'active')
            ->whereNotIn('student_id', $studentIds)
            ->update(['status' => 'ended', 'end_date' => $today]);

        foreach (array_diff($studentIds, $current) as $studentId) {
            Enrollment::create([
                'student_id' => $studentId,
                'batch_id' => $this->batch->id,
                'start_date' => $today,
                'status' => 'active',
            ]);
        }
    }

    public function render()
    {
        return view('livewire.batches.form', [
            'weekdays' => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
            'levels' => $this->courses->firstWhere('id', $this->course_id)?->levels ?? collect(),
        ]);
    }
}
