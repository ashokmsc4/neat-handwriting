<?php

namespace App\Livewire\Settings;

use App\Services\Backup;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Backups')]
class Backups extends Component
{
    public string $message = '';

    public function backupNow(Backup $backup): void
    {
        $this->message = 'Backup created: '.basename($backup->create());
    }

    public function render(Backup $backup)
    {
        return view('livewire.settings.backups', ['backups' => $backup->list()]);
    }
}
