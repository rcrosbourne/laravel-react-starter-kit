<?php

declare(strict_types=1);

namespace Tests\Unit\Setup;

use App\Setup\PassthruShellRunner;
use PHPUnit\Framework\TestCase;

final class ShellRunnerTest extends TestCase
{
    public function test_returns_true_when_the_underlying_command_succeeds(): void
    {
        ob_start();

        try {
            $this->assertTrue((new PassthruShellRunner)->run('true'));
        } finally {
            ob_end_clean();
        }
    }

    public function test_returns_false_when_the_underlying_command_fails(): void
    {
        ob_start();

        try {
            $this->assertFalse((new PassthruShellRunner)->run('false'));
        } finally {
            ob_end_clean();
        }
    }
}
