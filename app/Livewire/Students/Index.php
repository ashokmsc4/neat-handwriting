<?php

namespace App\Livewire\Students;

use App\Enums\StudentStatus;
use App\Models\Registration;
use App\Models\Student;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Students')]
class Index extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: 'active')]
    public string $status = 'active';

    public function updated(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $students = Student::query()
            ->with('guardian')
            ->when($this->status !== 'all', fn ($q) => $q->where('status', $this->status))
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(fn ($q) => $q->where('name', 'like', $term)
                    ->orWhereHas('guardian', fn ($g) => $g->where('name', 'like', $term)->orWhere('phone', 'like', $term)));
            })
            ->orderBy('name')
            ->paginate(25);

        return view('livewire.students.index', [
            'students' => $students,
            'statuses' => StudentStatus::cases(),
            'newRegistrations' => Registration::pending()->count(),
        ]);
    }
}
