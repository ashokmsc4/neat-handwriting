<?php

namespace App\Livewire;

use App\Models\Attendance;
use App\Models\Batch;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\Student;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Today')]
class Dashboard extends Component
{
    public function render()
    {
        $today = now();
        $monthStart = $today->copy()->startOfMonth();

        $todaysBatches = Batch::active()
            ->whereHas('schedules', fn ($q) => $q->where('weekday', $today->dayOfWeek))
            ->with([
                'course',
                'schedules' => fn ($q) => $q->where('weekday', $today->dayOfWeek),
                'sessions' => fn ($q) => $q->whereDate('date', $today)->where('status', 'held'),
            ])
            ->withCount('students')
            ->get()
            ->sortBy(fn ($batch) => $batch->schedules->first()->start_time);

        $monthMarks = Attendance::whereHas('classSession', fn ($q) => $q->where('date', '>=', $monthStart));
        $markCount = (clone $monthMarks)->count();

        // Birthdays in the next 7 days, matched on month and day.
        $upcoming = collect(range(0, 6))->map(fn ($d) => $today->copy()->addDays($d)->format('m-d'))->all();
        $birthdays = Student::active()->whereNotNull('dob')->get()
            ->filter(fn ($s) => in_array($s->dob->format('m-d'), $upcoming, true))
            ->sortBy(fn ($s) => array_search($s->dob->format('m-d'), $upcoming, true));

        return view('livewire.dashboard', [
            'today' => $today,
            'todaysBatches' => $todaysBatches,
            'activeStudents' => Student::active()->count(),
            'attendanceRate' => $markCount ? round((clone $monthMarks)->whereIn('status', ['present', 'late'])->count() / $markCount * 100) : null,
            'feesDue' => Invoice::outstanding()->with('payments')->get()->sum(fn ($invoice) => $invoice->balance()),
            'collected' => (float) Payment::where('paid_on', '>=', $monthStart)->sum('amount'),
            'birthdays' => $birthdays,
            'newRegistrations' => Registration::pending()->count(),
            'currency' => config('school.currency'),
        ]);
    }
}
