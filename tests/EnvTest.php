<?php

declare(strict_types=1);

namespace Winters\Env\Tests;

use PHPUnit\Framework\TestCase;
use Winters\Env\Env;
use Winters\Env\EnvException;

class EnvTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        $this->file = tempnam(sys_get_temp_dir(), 'env');
        file_put_contents($this->file, <<<'ENV'
            # database
            export DB_HOST=localhost
            DB_PORT=5432   # default port
            DB_URL="pgsql://${DB_HOST}:${DB_PORT}/app"
            APP_DEBUG=yes
            RATIO=0.75
            LITERAL='${DB_HOST} stays'
            HOSTS=a.example, b.example ,,c.example
            MULTI="line one\nline two"
            ENV);
    }

    protected function tearDown(): void
    {
        unlink($this->file);
        foreach (['DB_HOST', 'DB_PORT', 'DB_URL', 'APP_DEBUG', 'RATIO', 'LITERAL', 'HOSTS', 'MULTI'] as $name) {
            putenv($name);
            unset($_ENV[$name]);
        }
    }

    public function testTypedGetters(): void
    {
        $env = Env::load($this->file);

        $this->assertSame('localhost', $env->string('DB_HOST'));
        $this->assertSame(5432, $env->int('DB_PORT'));
        $this->assertSame('pgsql://localhost:5432/app', $env->string('DB_URL'));
        $this->assertTrue($env->bool('APP_DEBUG'));
        $this->assertSame(0.75, $env->float('RATIO'));
        $this->assertSame('${DB_HOST} stays', $env->string('LITERAL'));
        $this->assertSame(['a.example', 'b.example', 'c.example'], $env->list('HOSTS'));
        $this->assertSame("line one\nline two", $env->string('MULTI'));
    }

    public function testRealEnvironmentWins(): void
    {
        putenv('DB_HOST=db.internal');
        $this->assertSame('db.internal', Env::load($this->file)->string('DB_HOST'));
    }

    public function testMissingAndInvalid(): void
    {
        $env = Env::load($this->file);
        $this->assertSame(10, $env->int('NOT_THERE_AT_ALL', 10));

        $this->expectException(EnvException::class);
        $env->int('DB_HOST');
    }
}
