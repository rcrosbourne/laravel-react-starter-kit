<?php

declare(strict_types=1);

namespace App\Setup;

use Illuminate\Filesystem\Filesystem;

final readonly class TeamsInstaller
{
    public function __construct(
        private Filesystem $files,
        private string $appBasePath,
    ) {}

    /**
     * Copy every file from the given stubs directory into the project root,
     * preserving directory structure, then splice teams routes into web.php.
     */
    public function install(string $stubsRoot): void
    {
        if (! $this->files->isDirectory($stubsRoot)) {
            return;
        }

        $this->copyAll($stubsRoot);
        $this->spliceRoutes();
    }

    private function copyAll(string $stubsRoot): void
    {
        foreach ($this->files->allFiles($stubsRoot) as $file) {
            $relative = mb_ltrim(
                str_replace($stubsRoot, '', $file->getPathname()),
                DIRECTORY_SEPARATOR,
            );
            $destination = $this->appBasePath.DIRECTORY_SEPARATOR.$relative;
            $destinationDir = dirname($destination);

            if (! $this->files->isDirectory($destinationDir)) {
                $this->files->makeDirectory($destinationDir, 0755, true);
            }

            $this->files->copy($file->getPathname(), $destination);
        }
    }

    private function spliceRoutes(): void
    {
        $teamsRoutesFile = $this->appBasePath.'/routes/web.teams.php';
        $webRoutesFile = $this->appBasePath.'/routes/web.php';

        if (! $this->files->exists($teamsRoutesFile) || ! $this->files->exists($webRoutesFile)) {
            return;
        }

        $teamsContent = $this->files->get($teamsRoutesFile);

        preg_match_all('/^use\s+[^;]+;\s*$/m', $teamsContent, $useMatches);
        $teamsUses = implode("\n", $useMatches[0]);

        $teamsRoutes = (string) preg_replace('/^<\?php\s*/', '', $teamsContent);
        $teamsRoutes = (string) preg_replace('/^declare\(strict_types=1\);\s*/m', '', $teamsRoutes);
        $teamsRoutes = (string) preg_replace('/^use\s+[^;]+;\s*$/m', '', $teamsRoutes);

        $webContent = $this->files->get($webRoutesFile);

        if ($teamsUses !== '') {
            $webContent = (string) preg_replace(
                '/((?:^use\s+[^;]+;\s*$\n?)+)/m',
                sprintf('$1%s%s', $teamsUses, PHP_EOL),
                $webContent,
                1,
            );
        }

        $this->files->put(
            $webRoutesFile,
            mb_rtrim($webContent)."\n\n// Teams routes\n".mb_trim($teamsRoutes)."\n",
        );

        $this->files->delete($teamsRoutesFile);
    }
}
