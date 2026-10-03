<div class="space-y-4">
    <x-back-header :href="route('more')" title="Class details" />

    <form wire:submit="save" class="card grid gap-4 p-4 sm:grid-cols-2 lg:p-6">
        <div class="sm:col-span-2">
            <label for="school_name" class="label">Class name</label>
            <input wire:model="school_name" id="school_name" type="text" class="input">
            <p class="mt-1 text-xs text-muted">Shown in the app and on receipts.</p>
            @error('school_name') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="school_phone" class="label">Phone</label>
            <input wire:model="school_phone" id="school_phone" type="tel" class="input">
            @error('school_phone') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="fee_due_day" class="label">Monthly fee due on day</label>
            <input wire:model="fee_due_day" id="fee_due_day" type="number" inputmode="numeric" min="1" max="28" class="input">
            @error('fee_due_day') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div class="sm:col-span-2">
            <label for="school_address" class="label">Address</label>
            <textarea wire:model="school_address" id="school_address" rows="2" class="input"></textarea>
            @error('school_address') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="receipt_prefix" class="label">Receipt number prefix</label>
            <input wire:model="receipt_prefix" id="receipt_prefix" type="text" class="input uppercase">
            <p class="mt-1 text-xs text-muted">Receipts look like {{ $receipt_prefix ?: 'NH' }}-{{ now()->year }}-0001.</p>
            @error('receipt_prefix') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div class="flex items-end sm:justify-end">
            <button type="submit" class="btn-primary w-full sm:w-auto">Save</button>
        </div>
    </form>
</div>
