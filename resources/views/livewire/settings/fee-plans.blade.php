<div class="space-y-4">
    <x-back-header :href="route('more')" title="Fee plans">
        <button type="button" wire:click="edit" class="btn-primary shrink-0">+ Add</button>
    </x-back-header>

    @if ($editingId !== null)
        <form wire:submit="save" class="card grid gap-4 p-4 sm:grid-cols-2 lg:p-6">
            <h2 class="section-title sm:col-span-2">{{ $editingId ? 'Edit plan' : 'New plan' }}</h2>
            <div class="sm:col-span-2">
                <label for="name" class="label">Name</label>
                <input wire:model="name" id="name" type="text" placeholder="e.g. Monthly – Phonics" class="input">
                @error('name') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="type" class="label">Type</label>
                <select wire:model.live="type" id="type" class="input">
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="amount" class="label">Amount ({{ $currency }})</label>
                <input wire:model="amount" id="amount" type="number" inputmode="decimal" step="0.01" class="input">
                @error('amount') <p class="error">{{ $message }}</p> @enderror
            </div>
            @if ($type === 'pack')
                <div>
                    <label for="classes_count" class="label">Number of classes</label>
                    <input wire:model="classes_count" id="classes_count" type="number" inputmode="numeric" class="input">
                    @error('classes_count') <p class="error">{{ $message }}</p> @enderror
                </div>
            @endif
            <label class="flex items-center gap-2 self-end pb-3 text-sm text-ink">
                <input wire:model="active" type="checkbox" class="h-5 w-5 rounded accent-brand"> Available for new students
            </label>
            <div class="flex gap-2 sm:col-span-2 sm:justify-end">
                <button type="button" wire:click="$set('editingId', null)" class="btn-secondary flex-1 sm:flex-none">Cancel</button>
                <button type="submit" class="btn-primary flex-1 sm:flex-none">Save plan</button>
            </div>
        </form>
    @endif

    <div class="card divide-y divide-line">
        @forelse ($plans as $plan)
            <button type="button" wire:click="edit({{ $plan->id }})" wire:key="plan-{{ $plan->id }}" class="flex w-full items-center gap-3 p-4 text-left hover:bg-surface-2">
                <div class="min-w-0 flex-1">
                    <div class="font-medium text-ink">{{ $plan->name }}</div>
                    <div class="text-sm text-muted">{{ $plan->typeLabel() }}@if ($plan->classes_count) · {{ $plan->classes_count }} classes @endif</div>
                </div>
                <div class="text-right">
                    <div class="font-semibold text-ink">{{ $currency }}{{ number_format($plan->amount) }}</div>
                    @unless ($plan->active) <span class="text-xs text-muted">Not in use</span> @endunless
                </div>
            </button>
        @empty
            <div class="p-8 text-center text-muted">No fee plans yet.</div>
        @endforelse
    </div>
    <p class="text-sm text-muted">Monthly plans are billed automatically. Packs, term fees and one-time fees are added from a student's Fees tab with “+ Add charge”.</p>
</div>
