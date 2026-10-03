<?php

namespace App\Livewire\Reports;

use App\Enums\StudentStatus;
use App\Models\Attendance;
use App\Models\Batch;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Reports')]
class Index extends Component
{
    #[Url]
    public string $month = '';

    public function mount(): void
    {
        $this->month = $this->month ?: now()->format('Y-m');
    }

    public function render()
    {
        $start = Carbon::createFromFormat('Y-m-d', $this->month.'-01')->startOfDay();
        $end = $start->copy()->endOfMonth();

        $batches = Batch::with(['sessions' => fn ($q) => $q->where('status', 'held')->whereBetween('date', [$start, $end])->withCount([
            'attendance as marks',
            'attendance as attended' => fn ($a) => $a->whereIn('status', ['present', 'late']),
        ])])->orderBy('name')->get()
            ->map(fn ($b) => [
                'name' => $b->name,
                'classes' => $b->sessions->count(),
                'rate' => $b->sessions->sum('marks') ? round($b->sessions->sum('attended') / $b->sessions->sum('marks') * 100) : null,
            ])
            ->filter(fn ($b) => $b['classes'] > 0);

        $collections = collect(range(5, 0))->map(function ($ago) use ($start) {
            $m = $start->copy()->subMonths($ago);

            return [
                'label' => $m->format('M'),
                'total' => (float) Payment::whereBetween('paid_on', [$m->copy()->startOfMonth(), $m->copy()->endOfMonth()])->sum('amount'),
            ];
        });

        $marks = Attendance::whereHas('classSession', fn ($q) => $q->whereBetween('date', [$start, $end]));

        return view('livewire.reports.index', [
            'start' => $start,
            'end' => $end,
            'batches' => $batches,
            'collections' => $collections,
            'maxCollection' => max(1, $collections->max('total')),
            'attendanceRate' => ($total = (clone $marks)->count()) ? round((clone $marks)->whereIn('status', ['present', 'late'])->count() / $total * 100) : null,
            'outstanding' => Invoice::outstanding()->with('payments')->get()->sum(fn ($i) => $i->balance()),
            'statusCounts' => collect(StudentStatus::cases())->mapWithKeys(fn ($s) => [$s->label() => Student::where('status', $s)->count()]),
            'joined' => Student::whereBetween('joined_on', [$start, $end])->count(),
            'currency' => config('school.currency'),
        ]);
    }
}
