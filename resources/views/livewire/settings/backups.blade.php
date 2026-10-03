<div class="space-y-4">
    <x-back-header :href="route('more')" title="Backups">
        <button type="button" wire:click="backupNow" wire:loading.attr="disabled" class="btn-primary shrink-0">
            <span wire:loading.remove wire:target="backupNow">Back up now</span>
            <span wire:loading wire:target="backupNow">Working…</span>
        </button>
    </x-back-header>

    @if ($message)
        <div class="rounded-xl bg-success-soft px-4 py-3 text-sm text-success" role="status">{{ $message }}</div>
    @endif

    <p class="text-sm text-muted">
        A backup of all students, attendance, progress, fees and handwriting photos is made automatically every night,
        and the last {{ \App\Services\Backup::KEEP }} are kept. Download one now and then and keep it somewhere safe, such as Google Drive.
    </p>

    <div class="card divide-y divide-line">
        @forelse ($backups as $file)
            <div wire:key="bk-{{ $file['name'] }}" class="flex items-center gap-3 p-4">
                <div class="min-w-0 flex-1">
                    <div class="font-medium text-ink">{{ \Illuminate\Support\Carbon::createFromTimestamp($file['time'])->timezone(config('app.timezone'))->format('j M Y, g:i A') }}</div>
                    <div class="text-sm text-muted">{{ number_format($file['size'] / 1048576, 1) }} MB</div>
                </div>
                <a href="{{ route('backups.download', $file['name']) }}" class="btn-secondary">Download</a>
            </div>
        @empty
            <div class="p-8 text-center text-muted">No backups yet. Tap “Back up now” to make the first one.</div>
        @endforelse
    </div>
</div>
