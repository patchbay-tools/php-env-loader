<?php

declare(strict_types=1);

namespace Winters\Env;

use RuntimeException;

class EnvException extends RuntimeException
{
    public static function missing(string $name): self
    {
        return new self(sprintf('Environment variable %s is not set', $name));
    }

    public static function invalid(string $name, string $type, string $value): self
    {
        return new self(sprintf('Environment variable %s is not a valid %s: "%s"', $name, $type, $value));
    }

    public static function syntax(string $file, int $line): self
    {
        return new self(sprintf('Syntax error in %s on line %d', $file, $line));
    }
}
