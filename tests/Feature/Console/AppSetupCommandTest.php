<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Setup\ShellRunner;
use Illuminate\Filesystem\Filesystem;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

final class AppSetupCommandTest extends TestCase
{
    public function test_exits_early_when_stubs_directory_is_missing_and_force_is_not_passed(): void
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('isDirectory')->with(base_path('stubs'))->andReturn(false);
        $this->app->instance(Filesystem::class, $files);

        $shell = $this->mockShellRunner();
        $shell->shouldNotReceive('run');

        $this->artisan('app:setup')
            ->expectsOutputToContain('stubs/ directory not found')
            ->assertSuccessful();
    }

    public function test_runs_full_pipeline_with_no_teams_flag_and_no_env_file(): void
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('isDirectory')->with(base_path('stubs'))->andReturn(true, true);
        $files->shouldReceive('exists')->with(base_path('.env'))->andReturn(false);
        $files->shouldReceive('deleteDirectory')->with(base_path('stubs'))->once()->andReturn(true);
        $this->app->instance(Filesystem::class, $files);

        $shell = $this->mockShellRunner();
        $shell->shouldReceive('run')->times(4)->andReturn(true);

        $this->artisan('app:setup', ['--no-teams' => true])
            ->expectsOutputToContain('Setup complete.')
            ->expectsOutputToContain('Teams not enabled')
            ->assertSuccessful();
    }

    public function test_runs_full_pipeline_with_teams_flag_and_mutates_env_when_present(): void
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('isDirectory')->with(base_path('stubs'))->andReturn(true, true);
        $files->shouldReceive('exists')->with(base_path('.env'))->andReturn(true);
        $files->shouldReceive('get')->with(base_path('.env'))
            ->andReturn("APP_NAME=Laravel\nAPP_URL=http://localhost\nDB_DATABASE=laravel\n");
        $files->shouldReceive('put')->with(base_path('.env'), Mockery::type('string'))->once();
        $files->shouldReceive('deleteDirectory')->with(base_path('stubs'))->once()->andReturn(true);
        $this->app->instance(Filesystem::class, $files);

        $shell = $this->mockShellRunner();
        $shell->shouldReceive('run')->times(4)->andReturn(true);

        $this->artisan('app:setup', ['--teams' => true])
            ->expectsOutputToContain('Setup complete.')
            ->expectsOutputToContain('Teams enabled')
            ->assertSuccessful();
    }

    public function test_force_flag_proceeds_even_when_stubs_directory_is_missing(): void
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('isDirectory')->with(base_path('stubs'))->andReturn(false, false);
        $files->shouldReceive('exists')->with(base_path('.env'))->andReturn(false);
        $this->app->instance(Filesystem::class, $files);

        $shell = $this->mockShellRunner();
        $shell->shouldReceive('run')->times(4)->andReturn(true);

        $this->artisan('app:setup', ['--no-teams' => true, '--force' => true])
            ->expectsOutputToContain('Setup complete.')
            ->assertSuccessful();
    }

    public function test_prompts_for_teams_choice_when_no_flags_are_provided(): void
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('isDirectory')->with(base_path('stubs'))->andReturn(true, true);
        $files->shouldReceive('exists')->with(base_path('.env'))->andReturn(false);
        $files->shouldReceive('deleteDirectory')->with(base_path('stubs'))->once()->andReturn(true);
        $this->app->instance(Filesystem::class, $files);

        $shell = $this->mockShellRunner();
        $shell->shouldReceive('run')->times(4)->andReturn(true);

        $this->artisan('app:setup')
            ->expectsConfirmation('Add teams support to your application?', 'no')
            ->expectsOutputToContain('Teams not enabled')
            ->assertSuccessful();
    }

    private function mockShellRunner(): MockInterface
    {
        $shell = Mockery::mock(ShellRunner::class);
        $this->app->instance(ShellRunner::class, $shell);

        return $shell;
    }
}
