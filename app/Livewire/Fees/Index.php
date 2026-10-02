<?php

namespace App\Livewire\Fees;

use App\Models\Invoice;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Fees')]
class Index extends Component
{
    public function render()
    {
        return view('livewire.fees.index', [
            'invoices' => Invoice::outstanding()
                ->with(['student.guardian', 'payments'])
                ->orderBy('due_date')
                ->get(),
        ]);
    }
}
