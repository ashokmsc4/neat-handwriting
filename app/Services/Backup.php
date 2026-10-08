<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

/**
 * Whole-app backups as a single zip: every data table as JSON plus the
 * handwriting photos. Pure PHP, so it works on shared hosting without mysqldump.
 */
class Backup
{
    public const DIR = 'backups';

    public const KEEP = 14;

    /** Tables in restore order (parents before children). */
    public const TABLES = [
        'users', 'guardians', 'students', 'courses', 'levels', 'skills', 'fee_plans',
        'batches', 'batch_schedules', 'enrollments', 'class_sessions', 'attendance',
        'student_skills', 'assessments', 'assessment_scores', 'samples',
        'invoices', 'payments', 'registrations', 'settings', 'activity_log',
    ];

    public function create(): string
    {
        $disk = Storage::disk('local');
        $disk->makeDirectory(self::DIR);
        $name = self::DIR.'/neat-handwriting-'.now()->format('Y-m-d-His').'.zip';

        $zip = new ZipArchive;
        if ($zip->open($disk->path($name), ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create backup file.');
        }

        $zip->addFromString('manifest.json', json_encode([
            'app' => 'neat-handwriting',
            'created_at' => now()->toIso8601String(),
            'tables' => self::TABLES,
        ], JSON_PRETTY_PRINT));

        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table)) {
                $zip->addFromString("data/{$table}.json", json_encode(DB::table($table)->get(), JSON_UNESCAPED_UNICODE));
            }
        }

        foreach ($disk->allFiles('samples') as $file) {
            $zip->addFile($disk->path($file), $file);
        }

        $zip->close();
        $this->prune();

        return $name;
    }

    /** Replaces all current data with the contents of a backup zip. */
    public function restore(string $zipPath): void
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true || $zip->locateName('manifest.json') === false) {
            throw new RuntimeException('Not a Neat Handwriting backup.');
        }

        DB::transaction(function () use ($zip) {
            Schema::disableForeignKeyConstraints();

            foreach (array_reverse(self::TABLES) as $table) {
                DB::table($table)->delete();
            }

            foreach (self::TABLES as $table) {
                $json = $zip->getFromName("data/{$table}.json");
                $rows = $json ? json_decode($json, true) : [];

                foreach (array_chunk($rows, 200) as $chunk) {
                    DB::table($table)->insert($chunk);
                }
            }

            Schema::enableForeignKeyConstraints();
        });

        $disk = Storage::disk('local');
        $disk->deleteDirectory('samples');
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $file = $zip->getNameIndex($i);
            if (str_starts_with($file, 'samples/') && ! str_contains($file, '..') && ! str_ends_with($file, '/')) {
                $disk->put($file, $zip->getFromIndex($i));
            }
        }

        $zip->close();
    }

    /** @return array<int, array{name: string, size: int, time: int}> newest first */
    public function list(): array
    {
        $disk = Storage::disk('local');

        return collect($disk->files(self::DIR))
            ->filter(fn ($f) => str_ends_with($f, '.zip'))
            ->map(fn ($f) => ['name' => basename($f), 'size' => $disk->size($f), 'time' => $disk->lastModified($f)])
            ->sortByDesc('name')
            ->values()
            ->all();
    }

    private function prune(): void
    {
        foreach (array_slice($this->list(), self::KEEP) as $old) {
            Storage::disk('local')->delete(self::DIR.'/'.$old['name']);
        }
    }
}
