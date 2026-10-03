<?php

namespace App\Livewire\Settings;

use App\Models\FeePlan;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Fee plans')]
class FeePlans extends Component
{
    /** Plan being edited; 0 means a new plan, null means the form is closed. */
    public ?int $editingId = null;

    public string $name = '';

    public string $type = 'monthly';

    public string $amount = '';

    public string $classes_count = '';

    public bool $active = true;

    public function edit(int $id = 0): void
    {
        $this->resetValidation();
        $plan = $id ? FeePlan::findOrFail($id) : new FeePlan(['type' => 'monthly', 'active' => true]);

        $this->editingId = $id;
        $this->name = (string) $plan->name;
        $this->type = $plan->type;
        $this->amount = $plan->amount !== null ? (string) (float) $plan->amount : '';
        $this->classes_count = (string) $plan->classes_count;
        $this->active = (bool) $plan->active;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => 'required|string|max:80',
            'type' => ['required', Rule::in(array_keys(FeePlan::TYPES))],
            'amount' => 'required|numeric|min:0|max:1000000',
            'classes_count' => 'nullable|required_if:type,pack|integer|min:1|max:500',
            'active' => 'boolean',
        ]);
        $data['classes_count'] = $data['type'] === 'pack' ? $data['classes_count'] : null;

        $this->editingId
            ? FeePlan::findOrFail($this->editingId)->update($data)
            : FeePlan::create($data);

        $this->editingId = null;
    }

    public function render()
    {
        return view('livewire.settings.fee-plans', [
            'plans' => FeePlan::orderByDesc('active')->orderBy('name')->get(),
            'types' => FeePlan::TYPES,
            'currency' => config('school.currency'),
        ]);
    }
}
