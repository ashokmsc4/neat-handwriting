<?php

namespace App\Livewire;

use App\Models\Batch;
use App\Models\Invoice;
use App\Models\Student;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Today')]
class Dashboard extends Component
{
    public function render()
    {
        $today = now();

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

        $outstanding = Invoice::outstanding()->with('payments')->get();

        return view('livewire.dashboard', [
            'today' => $today,
            'todaysBatches' => $todaysBatches,
            'activeStudents' => Student::active()->count(),
            'activeBatches' => Batch::active()->count(),
            'feesDue' => $outstanding->sum(fn ($invoice) => $invoice->amount - $invoice->discount - $invoice->payments->sum('amount')),
            'feesDueCount' => $outstanding->count(),
        ]);
    }
}
