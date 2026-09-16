# php-env-loader

Tiny `.env` loader with typed getters. One class, no dependencies.

- `KEY=value`, `export KEY=value`, `# comments`, trailing ` # comments` on unquoted values
- `"double quotes"` with `\n`, `\t` escapes and `${VAR}` interpolation
- `'single quotes'` taken literally
- variables already set in the real environment are **not** overwritten (pass
  `override: true` if you want that)

## Install

```
composer require kwinters/php-env-loader
```

## Usage

```php
use Winters\Env\Env;

$env = Env::load(__DIR__ . '/.env');

$dsn     = $env->string('DB_URL');
$port    = $env->int('DB_PORT', 5432);
$debug   = $env->bool('APP_DEBUG', false);   // true/false, yes/no, on/off, 1/0
$ratio   = $env->float('SAMPLE_RATIO', 1.0);
$hosts   = $env->list('TRUSTED_PROXIES');    // "a, b, c" -> ['a', 'b', 'c']
```

A getter without a default throws `Winters\Env\EnvException` when the variable is missing, and
every typed getter throws when the value doesn't parse, so misconfiguration fails at boot
instead of somewhere later.

A missing `.env` file is not an error: in production everything usually comes from the real
environment, and the getters read it the same way.

## Tests

```
composer install
vendor/bin/phpunit tests
```
