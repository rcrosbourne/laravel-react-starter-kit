<?php

declare(strict_types=1);

namespace App\Setup;

use Illuminate\Support\Str;

final class EnvMutator
{
    /**
     * Apply project-specific overrides to the given .env contents.
     */
    public static function mutate(string $contents, string $project): string
    {
        $appName = Str::of($project)->replace(['-', '_'], ' ')->title()->toString();
        $dbName = Str::of($project)->replace('-', '_')->snake()->lower()->toString();
        $appUrl = sprintf('http://%s.test', $project);

        $contents = (string) preg_replace('/^APP_NAME=.*/m', sprintf('APP_NAME="%s"', $appName), $contents);
        $contents = (string) preg_replace('/^APP_URL=.*/m', 'APP_URL='.$appUrl, $contents);

        return (string) preg_replace('/^DB_DATABASE=.*/m', 'DB_DATABASE='.$dbName, $contents);
    }
}
