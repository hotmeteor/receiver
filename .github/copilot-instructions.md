# Receiver – Copilot Instructions

## Commands

```bash
# Run all tests
composer test
# or
vendor/bin/phpunit

# Run a single test file
vendor/bin/phpunit tests/GithubProviderTest.php

# Run a single test method
vendor/bin/phpunit --filter test_it_can_receive_github_webhook

# Fix code style
vendor/bin/php-cs-fixer fix
```

## Architecture

Receiver is a Laravel package that provides a driver-based webhook handling pipeline.

**Entry point:** `Receiver::driver('stripe')->receive($request)->ok()`

**Driver resolution:** `ReceiverManager` extends Laravel's `Manager` class. Each built-in driver has a corresponding `create{Name}Driver()` method that reads `services.{driver}.webhook_secret` from config and instantiates the provider.

**Provider lifecycle (in `AbstractProvider::receive()`):**
1. `handshake()` – if defined, runs first; returning a truthy value short-circuits handling and sends that as the response
2. `verify()` – if defined, returns `false` → 401 abort
3. `mapWebhook()` – builds a `Webhook` object from `getEvent()` and `getData()`
4. `handle()` – resolves and dispatches a handler class

**Handler class resolution:** `\App\Http\Handlers\{DriverName}\{EventClassName}`
- `{DriverName}` = provider class basename with `Provider` stripped (e.g., `GithubProvider` → `Github`)
- `{EventClassName}` = event string with non-alphanumeric chars replaced by spaces, then converted to StudlyCase (e.g., `customer.created` → `CustomerCreated`)
- GitHub is a special case: event = `{X-GitHub-Event}_{action}` (e.g., `issues_opened`)

**Handlers** must use the `Dispatchable` trait and accept `(string $event, array $data)` in the constructor. Implement `ShouldQueue` to queue them.

**Custom providers** are registered via `app('receiver')->extend('name', fn($app) => new MyProvider($secret))` in a service provider. Use `php artisan receiver:make <Name>` (or `--verified`) to scaffold.

## Key Conventions

**Config key for secrets:** Always `services.{driver}.webhook_secret` — see `ReceiverManager::buildProvider()`.

**`FakeProvider`** is available as the `fake` driver for testing purposes.

**Testing approach:** Tests use [Orchestra Testbench](https://github.com/orchestral/testbench). The base `TestCase` extends `\Orchestra\Testbench\TestCase` and registers `ReceiverServiceProvider`. Use `Mockery` to mock `Request` objects. To test handler dispatch in isolation, call `$provider->setHandlerNamespace('Your\\Test\\Namespace')` before `receive()`.

**Fixture handlers** in `tests/Fixtures/` use `Log::info('Webhook handled.')` to signal that dispatch occurred — assert with `Log::partialMock()->shouldReceive('info')->withArgs(['Webhook handled.'])`.

**Code style:** PHP CS Fixer with the config in `.php-cs-fixer.php`. Notable rules: single quotes, alpha-sorted imports (`ordered_imports`), trailing commas in multiline structures, blank line before `return`.
