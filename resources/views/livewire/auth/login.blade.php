<div class="w-full max-w-sm">
    <div class="mb-8 text-center">
        <img src="/icons/icon-192.png" alt="" class="mx-auto mb-4 h-16 w-16 rounded-2xl shadow-sm">
        <h1 class="text-2xl font-semibold text-ink">{{ \App\Models\Setting::get('school_name') }}</h1>
        <p class="mt-1 text-sm text-muted">Sign in to manage your classes</p>
    </div>

    <form wire:submit="login" class="card space-y-4 p-6">
        <div>
            <label for="email" class="label">Email</label>
            <input wire:model="email" id="email" type="email" autocomplete="username" autofocus class="input">
            @error('email') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="label">Password</label>
            <input wire:model="password" id="password" type="password" autocomplete="current-password" class="input">
            @error('password') <p class="error">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-muted">
            <input wire:model="remember" type="checkbox" class="h-4 w-4 rounded accent-brand">
            Keep me signed in on this device
        </label>

        <button type="submit" class="btn-primary w-full" wire:loading.attr="disabled">
            <span wire:loading.remove>Sign in</span>
            <span wire:loading>Signing in…</span>
        </button>
    </form>
</div>
