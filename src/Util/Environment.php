<?php

namespace Phariscope\EventStore\Util;

final class Environment
{
    public static function expand(string $value): string
    {
        // ${VAR}
        $value = preg_replace_callback('/\$\{([A-Z0-9_]+)\}/i', function (array $m): string {
            $env = self::getEnv($m[1]);
            return $env === false ? '' : $env;
        }, $value) ?? $value;

        // %env(VAR)%
        $value = preg_replace_callback('/%env\(([A-Z0-9_]+)\)%/i', function (array $m): string {
            $env = self::getEnv($m[1]);
            return $env === false ? '' : $env;
        }, $value) ?? $value;

        return $value;
    }

    /**
     * @return string|false
     */
    private static function getEnv(string $name)
    {
        $val = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);
        if ($val === false) {
            return false;
        }
        if (is_string($val)) {
            return $val;
        }
        // Convert scalars only; otherwise, drop to empty string
        if (is_int($val) || is_float($val) || is_bool($val)) {
            return (string) $val;
        }
        return '';
    }
}
