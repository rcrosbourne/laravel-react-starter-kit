<?php

declare(strict_types=1);

namespace Tests\Unit\Setup;

use App\Setup\TeamsInstaller;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;

final class TeamsInstallerTest extends TestCase
{
    private Filesystem $files;

    private string $base;

    private string $stubs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem;
        $this->base = sys_get_temp_dir().'/teams-installer-'.uniqid();
        $this->stubs = $this->base.'/stubs/teams';

        $this->files->makeDirectory($this->base, 0755, true);
    }

    protected function tearDown(): void
    {
        if ($this->files->isDirectory($this->base)) {
            $this->files->deleteDirectory($this->base);
        }

        parent::tearDown();
    }

    public function test_install_copies_files_preserving_directory_structure_and_splices_routes(): void
    {
        $this->files->makeDirectory($this->stubs.'/app/Models', 0755, true);
        $this->files->put(
            $this->stubs.'/app/Models/Team.php',
            "<?php\n\nfinal class Team {}\n",
        );

        $this->files->makeDirectory($this->stubs.'/routes', 0755, true);
        $this->files->put(
            $this->stubs.'/routes/web.teams.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            use App\Http\Controllers\Teams\TeamController;
            use Illuminate\Support\Facades\Route;

            Route::get('/teams/{team}', [TeamController::class, 'show'])->name('teams.show');
            PHP,
        );

        $this->files->makeDirectory($this->base.'/routes', 0755, true);
        $this->files->put(
            $this->base.'/routes/web.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            use Illuminate\Support\Facades\Route;

            Route::get('/', fn () => 'home')->name('home');
            PHP,
        );

        new TeamsInstaller($this->files, $this->base)->install($this->stubs);

        $this->assertFileExists($this->base.'/app/Models/Team.php');
        $this->assertSame(
            "<?php\n\nfinal class Team {}\n",
            $this->files->get($this->base.'/app/Models/Team.php'),
        );

        $this->assertFileDoesNotExist($this->base.'/routes/web.teams.php');

        $web = $this->files->get($this->base.'/routes/web.php');

        $this->assertStringContainsString('use App\\Http\\Controllers\\Teams\\TeamController;', $web);
        $this->assertStringContainsString('use Illuminate\\Support\\Facades\\Route;', $web);
        $this->assertStringContainsString('// Teams routes', $web);
        $this->assertStringContainsString("Route::get('/teams/{team}', [TeamController::class, 'show'])->name('teams.show');", $web);
        // Use statements appear before the closure-based home route.
        $useEnd = mb_strrpos($web, "\nuse ") ?: 0;
        $homePos = mb_strpos($web, "Route::get('/'") ?: 0;
        $this->assertGreaterThan($useEnd, $homePos);
    }

    public function test_install_is_a_no_op_when_stubs_directory_is_missing(): void
    {
        $missing = $this->base.'/missing-stubs';

        new TeamsInstaller($this->files, $this->base)->install($missing);

        $this->assertDirectoryDoesNotExist($missing);
        $this->assertSame([], $this->files->files($this->base));
    }

    public function test_splice_is_skipped_when_web_php_does_not_exist(): void
    {
        $this->files->makeDirectory($this->stubs.'/routes', 0755, true);
        $this->files->put(
            $this->stubs.'/routes/web.teams.php',
            "<?php\n\nuse Foo\\Bar;\n\nRoute::get('/x', [Bar::class, 'x']);\n",
        );

        new TeamsInstaller($this->files, $this->base)->install($this->stubs);

        // web.teams.php was copied but web.php was never present, so splice early-returns
        // and web.teams.php remains.
        $this->assertFileExists($this->base.'/routes/web.teams.php');
        $this->assertFileDoesNotExist($this->base.'/routes/web.php');
    }

    public function test_splice_dedupes_use_statements_already_present_in_web_php(): void
    {
        $this->files->makeDirectory($this->stubs.'/routes', 0755, true);
        $this->files->put(
            $this->stubs.'/routes/web.teams.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            use App\Http\Controllers\Teams\TeamController;
            use Illuminate\Support\Facades\Route;

            Route::get('/teams/{team}', [TeamController::class, 'show'])->name('teams.show');
            PHP,
        );

        $this->files->makeDirectory($this->base.'/routes', 0755, true);
        $this->files->put(
            $this->base.'/routes/web.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            use Illuminate\Support\Facades\Route;

            Route::get('/', fn () => 'home')->name('home');
            PHP,
        );

        new TeamsInstaller($this->files, $this->base)->install($this->stubs);

        $web = $this->files->get($this->base.'/routes/web.php');

        // Illuminate\Support\Facades\Route should appear EXACTLY once, even
        // though both files import it. Otherwise PHP throws "Cannot use ...
        // because the name is already in use".
        $this->assertSame(
            1,
            mb_substr_count($web, 'use Illuminate\\Support\\Facades\\Route;'),
        );
        // The new TeamController import was spliced in.
        $this->assertStringContainsString('use App\\Http\\Controllers\\Teams\\TeamController;', $web);
    }

    public function test_splice_handles_teams_routes_with_no_use_statements(): void
    {
        $this->files->makeDirectory($this->stubs.'/routes', 0755, true);
        $this->files->put(
            $this->stubs.'/routes/web.teams.php',
            "<?php\n\nRoute::get('/ping', fn () => 'pong');\n",
        );

        $this->files->makeDirectory($this->base.'/routes', 0755, true);
        $this->files->put(
            $this->base.'/routes/web.php',
            "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\nRoute::get('/', fn () => 'home');\n",
        );

        new TeamsInstaller($this->files, $this->base)->install($this->stubs);

        $web = $this->files->get($this->base.'/routes/web.php');
        $this->assertStringContainsString('// Teams routes', $web);
        $this->assertStringContainsString("Route::get('/ping', fn () => 'pong');", $web);
        $this->assertFileDoesNotExist($this->base.'/routes/web.teams.php');
    }
}
