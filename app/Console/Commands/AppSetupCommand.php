<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;

final class AppSetupCommand extends Command
{
    protected $signature = 'app:setup
                            {--teams : Install with teams support}
                            {--no-teams : Install without teams support}
                            {--force : Re-run even if stubs/ has been removed}';

    protected $description = 'Configure a fresh clone of the starter kit (env, optional teams, build, migrate)';

    public function handle(Filesystem $files): int
    {
        $stubsPath = base_path('stubs');

        if (! $files->isDirectory($stubsPath) && ! $this->option('force')) {
            $this->components->info('stubs/ directory not found — setup already ran. Use --force to re-run.');

            return self::SUCCESS;
        }

        $this->mutateEnv($files);

        $teams = $this->resolveTeamsChoice();

        if ($teams) {
            $this->copyTeamsStubs();
        }

        if ($files->isDirectory($stubsPath)) {
            $files->deleteDirectory($stubsPath);
        }

        $this->runShell('php artisan boost:install --no-interaction');
        $this->runShell('bun install');
        $this->runShell('bun run build');
        $this->runShell('php artisan migrate --graceful');

        $this->printNextSteps($teams);

        return self::SUCCESS;
    }

    private function resolveTeamsChoice(): bool
    {
        if ($this->option('teams')) {
            return true;
        }

        if ($this->option('no-teams')) {
            return false;
        }

        return confirm(
            label: 'Add teams support to your application?',
            default: false,
        );
    }

    private function mutateEnv(Filesystem $files): void
    {
        $envPath = base_path('.env');

        if (! $files->exists($envPath)) {
            return;
        }

        $project = basename(base_path());

        $appName = Str::of($project)->replace(['-', '_'], ' ')->title()->toString();
        $dbName = Str::of($project)->replace('-', '_')->snake()->lower()->toString();
        $appUrl = sprintf('http://%s.test', $project);

        $contents = $files->get($envPath);

        $contents = (string) preg_replace('/^APP_NAME=.*/m', sprintf('APP_NAME="%s"', $appName), $contents);
        $contents = (string) preg_replace('/^APP_URL=.*/m', 'APP_URL='.$appUrl, $contents);
        $contents = (string) preg_replace('/^DB_DATABASE=.*/m', 'DB_DATABASE='.$dbName, $contents);

        $files->put($envPath, $contents);
    }

    private function copyTeamsStubs(): void
    {
        // Implemented in Phase 8.
    }

    private function runShell(string $command): void
    {
        $this->components->task($command, function () use ($command): bool {
            $exitCode = 0;
            passthru($command, $exitCode);

            return $exitCode === 0;
        });
    }

    private function printNextSteps(bool $teams): void
    {
        $appUrl = config('app.url');

        $this->newLine();
        $this->components->info('Setup complete.');
        $this->components->bulletList([
            'Update .env if you need different DB credentials',
            'Create the database in Herd if it does not exist yet',
            sprintf('Run `composer dev` to start queue + pail + vite (Herd serves %s)', is_string($appUrl) ? $appUrl : ''),
            'Run `composer test:coverage` for local coverage via Herd',
            $teams ? 'Teams enabled: register a user to auto-create their personal team' : 'Teams not enabled (run app:setup --teams in a fresh clone to enable)',
        ]);
    }
}
