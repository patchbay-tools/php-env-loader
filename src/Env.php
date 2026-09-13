<?php

declare(strict_types=1);

namespace Winters\Env;

class Env
{
    /** @var array<string, string> */
    protected array $values = [];

    /**
     * Load a .env file. Variables already present in the real environment win, unless
     * $override is true, so production settings are never replaced by a stray file.
     */
    public static function load(string $path, bool $override = false): static
    {
        $env = new static();

        if (!is_readable($path)) {
            return $env;
        }

        foreach (static::parse((string) file_get_contents($path), $path) as $name => $value) {
            if (!$override && getenv($name) !== false) {
                $value = (string) getenv($name);
            } else {
                putenv("$name=$value");
                $_ENV[$name] = $value;
            }
            $env->values[$name] = $value;
        }

        return $env;
    }

    /**
     * @return array<string, string>
     */
    public static function parse(string $contents, string $source = '.env'): array
    {
        $values = [];
        $lines = preg_split('/\r\n|\n|\r/', $contents);

        foreach ($lines as $i => $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            if (!preg_match('/^(?:export\s+)?([A-Za-z_][A-Za-z0-9_.]*)\s*=\s*(.*)$/', $line, $m)) {
                throw EnvException::syntax($source, $i + 1);
            }

            [, $name, $raw] = $m;
            $values[$name] = static::value($raw, $values);
        }

        return $values;
    }

    protected static function value(string $raw, array $known): string
    {
        if ($raw === '') {
            return '';
        }

        // single quotes are literal
        if ($raw[0] === "'" && preg_match("/^'([^']*)'/", $raw, $m)) {
            return $m[1];
        }

        if ($raw[0] === '"' && preg_match('/^"((?:[^"\\\\]|\\\\.)*)"/', $raw, $m)) {
            $value = strtr($m[1], ['\\n' => "\n", '\\t' => "\t", '\\"' => '"', '\\\\' => '\\']);
        } else {
            // unquoted: strip a trailing " # comment"
            $value = trim(preg_replace('/\s+#.*$/', '', $raw));
        }

        return preg_replace_callback('/\$\{([A-Za-z_][A-Za-z0-9_.]*)\}/', function ($m) use ($known) {
            return $known[$m[1]] ?? (getenv($m[1]) ?: '');
        }, $value);
    }

    public function has(string $name): bool
    {
        return $this->raw($name) !== null;
    }

    public function string(string $name, ?string $default = null): string
    {
        return $this->raw($name) ?? $default ?? throw EnvException::missing($name);
    }

    public function int(string $name, ?int $default = null): int
    {
        $value = $this->raw($name);
        if ($value === null) {
            return $default ?? throw EnvException::missing($name);
        }

        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            throw EnvException::invalid($name, 'integer', $value);
        }

        return (int) $value;
    }

    public function float(string $name, ?float $default = null): float
    {
        $value = $this->raw($name);
        if ($value === null) {
            return $default ?? throw EnvException::missing($name);
        }

        if (!is_numeric($value)) {
            throw EnvException::invalid($name, 'number', $value);
        }

        return (float) $value;
    }

    public function bool(string $name, ?bool $default = null): bool
    {
        $value = $this->raw($name);
        if ($value === null) {
            return $default ?? throw EnvException::missing($name);
        }

        $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($bool === null) {
            throw EnvException::invalid($name, 'boolean', $value);
        }

        return $bool;
    }

    /**
     * @return list<string>
     */
    public function list(string $name, string $separator = ',', array $default = []): array
    {
        $value = $this->raw($name);
        if ($value === null || $value === '') {
            return $default;
        }

        return array_values(array_filter(array_map('trim', explode($separator, $value)), 'strlen'));
    }

    protected function raw(string $name): ?string
    {
        if (array_key_exists($name, $this->values)) {
            return $this->values[$name];
        }

        $value = getenv($name);
        return $value === false ? null : $value;
    }
}
