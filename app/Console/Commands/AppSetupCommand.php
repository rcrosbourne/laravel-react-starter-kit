<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Setup\EnvMutator;
use App\Setup\ShellRunner;
use App\Setup\TeamsInstaller;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

use function Laravel\Prompts\confirm;

final class AppSetupCommand extends Command
{
    protected $signature = 'app:setup
                            {--teams : Install with teams support}
                            {--no-teams : Install without teams support}
                            {--force : Re-run even if stubs/ has been removed}';

    protected $description = 'Configure a fresh clone of the starter kit (env, optional teams, build, migrate)';

    public function handle(Filesystem $files, ShellRunner $shell): int
    {
        $stubsPath = base_path('stubs');

        if (! $files->isDirectory($stubsPath) && ! $this->option('force')) {
            $this->components->info('stubs/ directory not found — setup already ran. Use --force to re-run.');

            return self::SUCCESS;
        }

        $this->mutateEnv($files);

        $teams = $this->resolveTeamsChoice();

        if ($teams) {
            $this->copyTeamsStubs($files);
        }

        if ($files->isDirectory($stubsPath)) {
            $files->deleteDirectory($stubsPath);
        }

        $this->runShell($shell, 'php artisan boost:install --no-interaction');
        $this->runShell($shell, 'bun install');
        $this->runShell($shell, 'bun run build');
        $this->runShell($shell, 'php artisan migrate --graceful');

        $this->printNextSteps($teams);

        return self::SUCCESS;
    }

    private function runShell(ShellRunner $shell, string $command): void
    {
        $this->components->task($command, fn (): bool => $shell->run($command));
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
        $contents = $files->get($envPath);

        $files->put($envPath, EnvMutator::mutate($contents, $project));
    }

    private function copyTeamsStubs(Filesystem $files): void
    {
        (new TeamsInstaller($files, base_path()))->install(base_path('stubs/teams'));
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
