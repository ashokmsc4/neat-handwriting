<?php

namespace App\Livewire\Batches;

use App\Models\Batch;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Batches')]
class Index extends Component
{
    public function render()
    {
        return view('livewire.batches.index', [
            'batches' => Batch::active()
                ->with(['course', 'level', 'schedules'])
                ->withCount('students')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
