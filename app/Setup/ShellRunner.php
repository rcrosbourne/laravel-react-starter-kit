<?php

declare(strict_types=1);

namespace App\Setup;

interface ShellRunner
{
    /**
     * Execute the given shell command, returning true on success.
     */
    public function run(string $command): bool;
}
