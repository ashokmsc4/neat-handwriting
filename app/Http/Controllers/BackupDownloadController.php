<?php

namespace App\Http\Controllers;

use App\Services\Backup;
use Illuminate\Support\Facades\Storage;

class BackupDownloadController extends Controller
{
    public function __invoke(string $file)
    {
        abort_unless(preg_match('/^neat-handwriting-[\d-]+\.zip$/', $file), 404);

        $path = Backup::DIR.'/'.$file;
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path);
    }
}
