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
        $webContent = $this->files->get($webRoutesFile);

        // Extract the FQCN inside each `use ...;` statement from both files so
        // we can splice only NEW imports into web.php and avoid duplicate-use
        // PHP fatal errors when the teams fragment already shares an import
        // (e.g. Illuminate\Support\Facades\Route).
        preg_match_all('/^use\s+([^;]+);[\t ]*$/m', $teamsContent, $teamsUseMatches);
        preg_match_all('/^use\s+([^;]+);[\t ]*$/m', $webContent, $existingUseMatches);

        $existingUses = array_map(mb_trim(...), $existingUseMatches[1]);
        $teamsUses = array_map(mb_trim(...), $teamsUseMatches[1]);
        $newUses = array_values(array_filter(
            $teamsUses,
            fn (string $fqcn): bool => ! in_array($fqcn, $existingUses, true),
        ));

        $teamsRoutes = (string) preg_replace('/^<\?php[\t ]*\n?/', '', $teamsContent);
        $teamsRoutes = (string) preg_replace('/^declare\(strict_types=1\);[\t ]*\n?/m', '', $teamsRoutes);
        $teamsRoutes = (string) preg_replace('/^use\s+[^;]+;[\t ]*\n?/m', '', $teamsRoutes);

        if ($newUses !== []) {
            // Merge the new FQCNs into the existing block, sort alphabetically,
            // and rewrite the entire `use` block in one shot — this matches what
            // pint's `ordered_imports` fixer expects so we don't leave a stray
            // second import group beneath the first.
            $mergedUses = array_values(array_unique([...$existingUses, ...$newUses]));
            sort($mergedUses, SORT_STRING);

            $renderedBlock = implode("\n", array_map(
                fn (string $fqcn): string => sprintf('use %s;', $fqcn),
                $mergedUses,
            ));

            $webContent = (string) preg_replace(
                '/(?:^use\s+[^;]+;[\t ]*\n?)+/m',
                $renderedBlock."\n",
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
