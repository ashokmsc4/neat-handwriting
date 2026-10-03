<?php

namespace App\Livewire\Settings;

use App\Models\Setting;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Class details')]
class School extends Component
{
    public string $school_name = '';

    public string $school_phone = '';

    public string $school_address = '';

    public int $fee_due_day = 10;

    public string $receipt_prefix = '';

    public function mount(): void
    {
        foreach (array_keys(Setting::DEFAULTS) as $key) {
            $this->{$key} = Setting::get($key) ?? '';
        }
    }

    public function save(): void
    {
        $data = $this->validate([
            'school_name' => 'required|string|max:80',
            'school_phone' => 'nullable|string|max:30',
            'school_address' => 'nullable|string|max:255',
            'fee_due_day' => 'required|integer|between:1,28',
            'receipt_prefix' => 'required|alpha_num|max:6',
        ]);

        Setting::put($data);

        session()->flash('status', 'Class details saved.');
        $this->redirectRoute('more', navigate: true);
    }

    public function render()
    {
        return view('livewire.settings.school');
    }
}
