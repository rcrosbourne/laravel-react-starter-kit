# Philosophy

This starter kit ships three opinions you can disable but not configure away.

## 1. Herd-first

Defaults assume Laravel Herd: SMTP mail to `127.0.0.1:2525`, app served at `http://<project>.test`, `composer test:coverage` uses `herd coverage`. Non-Herd users can still run the kit (the CI path proves it), but they may need to add `php artisan serve` alongside `composer dev` and adjust mail/coverage commands. The kit doesn't apologize for this — Herd is the assumed local development environment.

## 2. Postgres-first

Tests run against Postgres in development and CI. SQLite is not supported. This catches Postgres-only bugs (JSONB, array columns, case sensitivity, transaction isolation) before production. The cost is small: Herd ships Postgres, and `--parallel` Pest keeps the inner loop fast.

## 3. 100%-or-bust

Pest line coverage and type coverage are both gated at 100% in CI. Zero `@phpstan-ignore` directives. PHPStan at level max. The bar is high because the failure mode of "we'll get back to 100% later" is empirically: nobody does. Setting the gate at 100% from day one means it stays there. If a feature is genuinely too hard to test, the answer is to redesign the feature, not to lower the gate.
