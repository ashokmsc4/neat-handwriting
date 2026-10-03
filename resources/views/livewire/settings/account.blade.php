<div class="space-y-4">
    <x-back-header :href="route('more')" title="My account" />

    @if ($message)
        <div class="rounded-xl bg-success-soft px-4 py-3 text-sm text-success" role="status">{{ $message }}</div>
    @endif

    <form wire:submit="saveProfile" class="card grid gap-4 p-4 sm:grid-cols-2 lg:p-6">
        <h2 class="section-title sm:col-span-2">Details</h2>
        <div>
            <label for="name" class="label">Name</label>
            <input wire:model="name" id="name" type="text" autocomplete="name" class="input">
            @error('name') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="email" class="label">Email (used to sign in)</label>
            <input wire:model="email" id="email" type="email" autocomplete="username" class="input">
            @error('email') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div class="sm:col-span-2 sm:text-right">
            <button type="submit" class="btn-primary w-full sm:w-auto">Save details</button>
        </div>
    </form>

    <form wire:submit="savePassword" class="card grid gap-4 p-4 sm:grid-cols-2 lg:p-6">
        <h2 class="section-title sm:col-span-2">Change password</h2>
        <div class="sm:col-span-2">
            <label for="current_password" class="label">Current password</label>
            <input wire:model="current_password" id="current_password" type="password" autocomplete="current-password" class="input">
            @error('current_password') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="password" class="label">New password</label>
            <input wire:model="password" id="password" type="password" autocomplete="new-password" class="input">
            @error('password') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="password_confirmation" class="label">Repeat new password</label>
            <input wire:model="password_confirmation" id="password_confirmation" type="password" autocomplete="new-password" class="input">
        </div>
        <div class="sm:col-span-2 sm:text-right">
            <button type="submit" class="btn-primary w-full sm:w-auto">Change password</button>
        </div>
    </form>
</div>
