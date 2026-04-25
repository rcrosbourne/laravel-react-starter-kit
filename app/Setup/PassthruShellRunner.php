<?php

declare(strict_types=1);

namespace App\Setup;

final class PassthruShellRunner implements ShellRunner
{
    public function run(string $command): bool
    {
        $exitCode = 0;
        passthru($command, $exitCode);

        return $exitCode === 0;
    }
}
