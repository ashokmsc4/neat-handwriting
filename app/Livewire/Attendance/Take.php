<?php

namespace App\Livewire\Attendance;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Batch;
use App\Models\ClassSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Attendance')]
class Take extends Component
{
    public Batch $batch;

    public string $date;

    public ?string $startTime = null;

    /** @var array<int, string> student id => attendance status */
    public array $marks = [];

    public string $topicNote = '';

    public bool $alreadySaved = false;

    public function mount(Batch $batch, ?string $date = null): void
    {
        $this->batch = $batch;
        $day = $date ? Carbon::parse($date) : Carbon::today();
        abort_if($day->isAfter(Carbon::today()), 404);
        $this->date = $day->toDateString();

        $this->startTime = $batch->schedules()->where('weekday', $day->dayOfWeek)->orderBy('start_time')->value('start_time');

        $session = $this->findSession();
        $existing = $session ? $session->attendance()->pluck('status', 'student_id')->map(fn ($s) => $s->value) : collect();

        $this->alreadySaved = $session?->status === 'held';
        $this->topicNote = (string) $session?->topic_note;

        // Everyone starts as present so the teacher only taps the exceptions.
        foreach ($this->students() as $student) {
            $this->marks[$student->id] = $existing[$student->id] ?? AttendanceStatus::Present->value;
        }
    }

    private function findSession(): ?ClassSession
    {
        return $this->batch->sessions()
            ->whereDate('date', $this->date)
            ->when($this->startTime, fn ($q) => $q->where('start_time', $this->startTime), fn ($q) => $q->whereNull('start_time'))
            ->first();
    }

    private function students()
    {
        return $this->batch->students()->orderBy('name')->get(['students.id', 'students.name']);
    }

    public function markAll(string $status): void
    {
        foreach (array_keys($this->marks) as $id) {
            $this->marks[$id] = $status;
        }
    }

    public function save()
    {
        $this->validate([
            'marks' => 'array',
            'marks.*' => ['required', Rule::enum(AttendanceStatus::class)],
            'topicNote' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () {
            $session = $this->findSession() ?? new ClassSession([
                'batch_id' => $this->batch->id,
                'date' => $this->date,
                'start_time' => $this->startTime,
            ]);
            $session->fill(['status' => 'held', 'topic_note' => $this->topicNote ?: null])->save();

            $enrolled = $this->students()->pluck('id')->all();

            foreach ($this->marks as $studentId => $status) {
                if (! in_array((int) $studentId, $enrolled, true)) {
                    continue;
                }

                Attendance::updateOrCreate(
                    ['class_session_id' => $session->id, 'student_id' => $studentId],
                    ['status' => $status],
                );
            }
        });

        $absent = collect($this->marks)->filter(fn ($s) => $s === AttendanceStatus::Absent->value)->count();
        session()->flash('status', "Attendance saved for {$this->batch->name}: ".(count($this->marks) - $absent).' present, '.$absent.' absent.');

        return $this->redirectRoute('dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.attendance.take', [
            'students' => $this->students(),
            'statuses' => AttendanceStatus::cases(),
            'day' => Carbon::parse($this->date),
        ]);
    }
}
