<?php

namespace App\Livewire\Fees;

use App\Livewire\Concerns\RecordsPayments;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Billing;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Fees')]
class Index extends Component
{
    use RecordsPayments;

    #[Url(except: 'due')]
    public string $tab = 'due';

    #[Url(except: '')]
    public string $search = '';

    public function billThisMonth(Billing $billing): void
    {
        $count = $billing->generateMonthly(now()->startOfMonth());

        session()->flash('status', $count
            ? "Created {$count} invoice".($count === 1 ? '' : 's').' for '.now()->format('F').'.'
            : 'Everyone on a monthly plan is already billed for '.now()->format('F').'.');
    }

    public function render()
    {
        $monthStart = now()->startOfMonth();
        $studentFilter = fn ($q) => $q->where('name', 'like', '%'.$this->search.'%');

        $outstanding = Invoice::outstanding()
            ->with(['student.guardian', 'payments'])
            ->when($this->search !== '', fn ($q) => $q->whereHas('student', $studentFilter))
            ->orderBy('due_date')
            ->get();

        $payments = $this->tab === 'paid'
            ? Payment::with('invoice.student')
                ->where('paid_on', '>=', $monthStart)
                ->when($this->search !== '', fn ($q) => $q->whereHas('invoice.student', $studentFilter))
                ->orderByDesc('paid_on')->orderByDesc('id')
                ->get()
            : collect();

        return view('livewire.fees.index', [
            'invoices' => $outstanding,
            'payments' => $payments,
            'totalDue' => $outstanding->sum(fn ($i) => $i->balance()),
            'collectedThisMonth' => (float) Payment::where('paid_on', '>=', $monthStart)->sum('amount'),
            'currency' => config('school.currency'),
        ]);
    }
}
