<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * One migration to rule them all: seeds every module and runs every
 * module's console commands, so a single `php artisan migrate` leaves the
 * catalog fully populated.
 *
 * - seeders:  db:seed (Catalog + Learning module seeders)
 * - commands: catalog:import-words, catalog:import-irregular-verbs,
 *             catalog:import-phrases (only if worlds.md exists),
 *             catalog:clean-data --fix
 *
 * Runs ONCE (migrations are recorded). Heavy imports stream their output
 * live so you can see per-file progress. The seeders only run on a truly
 * empty DB — if languages already exist they are skipped, so this is safe
 * on both fresh and already-seeded databases.
 */
return new class extends Migration
{
    private int $bufferLength = 0;

    public function up(): void
    {
        if (DB::table('languages')->exists()) {
            $this->printLn('Already seeded — skipping module seeders.');
        } else {
            $this->runCommand('db:seed', ['--force' => true]);
        }

        $this->printImportPlan();

        $this->runStreaming('catalog:import-words');
        $this->runStreaming('catalog:import-irregular-verbs');

        if (is_file(base_path('worlds.md'))) {
            $this->runStreaming('catalog:import-phrases');
        } else {
            $this->printLn('Skipping catalog:import-phrases — worlds.md not found.');
        }

        $this->runCommand('catalog:clean-data', ['--fix' => true]);
    }

    public function down(): void
    {
        // Seeding/imports are not reversible; migrate:rollback just leaves the data.
    }

    private function printImportPlan(): void
    {
        $disk = Storage::disk('public');

        $wordFiles = collect($disk->allFiles('words'))
            ->filter(fn (string $f): bool => (bool) preg_match('/\.(xlsx|csv)$/iu', $f)
                && ! str_starts_with(basename($f), 'irregular-transcriptions'))
            ->values();

        $this->printLn('');
        $this->printLn(sprintf(
            'Import plan: %d dictionary file(s) under words/ (%s), irregular-verbs .xls: %s',
            $wordFiles->count(),
            $wordFiles->count() === 0 ? 'none' : 'streams live as each finishes',
            $disk->exists('All_700_Irregular_Verbs.xls') ? 'present' : 'MISSING',
        ));
    }

    private function runStreaming(string $command): void
    {
        $this->printLn('');
        $this->printLn('> '.$command);

        passthru('php '.escapeshellarg(base_path('artisan')).' '.$command, $exitCode);

        if ($exitCode !== 0) {
            throw new RuntimeException("{$command} exited with code {$exitCode}");
        }
    }

    private function runCommand(string $command, array $parameters = []): void
    {
        $exitCode = Artisan::call($command, $parameters);

        $output = substr(Artisan::output(), $this->bufferLength);
        $this->bufferLength = strlen(Artisan::output());

        if ($output !== '') {
            $this->printLn('$ '.$command);
            $this->printLn(trim($output));
        }

        if ($exitCode !== 0) {
            throw new RuntimeException("{$command} exited with code {$exitCode}");
        }
    }

    private function printLn(string $text): void
    {
        fwrite(STDOUT, $text."\n");
    }
};