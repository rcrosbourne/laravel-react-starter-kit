<?php

declare(strict_types=1);

namespace Tests\Unit\Setup;

use App\Setup\EnvMutator;
use PHPUnit\Framework\TestCase;

final class EnvMutatorTest extends TestCase
{
    public function test_replaces_app_name_app_url_and_db_database_with_project_specific_values(): void
    {
        $env = <<<'ENV_WRAP'
        APP_NAME=Laravel
        APP_ENV=local
        APP_URL=http://localhost
        DB_CONNECTION=pgsql
        DB_DATABASE=laravel
        ENV_WRAP;

        $mutated = EnvMutator::mutate($env, 'my-cool-app');

        $this->assertStringContainsString('APP_NAME="My Cool App"', $mutated);
        $this->assertStringContainsString('APP_URL=http://my-cool-app.test', $mutated);
        $this->assertStringContainsString('DB_DATABASE=my_cool_app', $mutated);
        $this->assertStringContainsString('APP_ENV=local', $mutated);
        $this->assertStringContainsString('DB_CONNECTION=pgsql', $mutated);
    }

    public function test_normalises_underscores_in_project_names_for_app_name_and_db(): void
    {
        $env = "APP_NAME=Laravel\nAPP_URL=http://localhost\nDB_DATABASE=laravel\n";

        $mutated = EnvMutator::mutate($env, 'my_starter_kit');

        $this->assertStringContainsString('APP_NAME="My Starter Kit"', $mutated);
        $this->assertStringContainsString('APP_URL=http://my_starter_kit.test', $mutated);
        $this->assertStringContainsString('DB_DATABASE=my_starter_kit', $mutated);
    }

    public function test_leaves_contents_untouched_when_keys_are_missing(): void
    {
        $env = "FOO=bar\nBAZ=qux\n";

        $this->assertSame($env, EnvMutator::mutate($env, 'whatever'));
    }
}
