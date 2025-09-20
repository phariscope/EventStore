<?php

namespace Phariscope\EventStore\Util;

/**
 * Utility class for expanding environment variables and resolving paths in configuration values.
 *
 * Supports multiple environment variable formats:
 * - ${VAR} format
 * - %env(VAR)% format (Symfony style)
 *
 * Also converts relative paths to absolute paths.
 */
final class Environment
{
    private const ENV_VARIABLE_PATTERN_BASH = '/\$\{([A-Z0-9_]+)\}/i';
    private const ENV_VARIABLE_PATTERN_SYMFONY = '/%env\(([A-Z0-9_]+)\)%/i';

    /**
     * Expands environment variables and resolves relative paths in the given value.
     *
     * @param string $value The value to expand
     * @return string The expanded value
     */
    public static function expand(string $value): string
    {
        $expandedValue = self::expandEnvironmentVariables($value);

        return self::resolveRelativePaths($expandedValue);
    }

    /**
     * Expands environment variables in both ${VAR} and %env(VAR)% formats.
     */
    private static function expandEnvironmentVariables(string $value): string
    {
        // Expand ${VAR} format
        $value = preg_replace_callback(
            self::ENV_VARIABLE_PATTERN_BASH,
            self::createEnvironmentVariableReplacer(),
            $value
        ) ?? $value;

        // Expand %env(VAR)% format (Symfony style)
        $value = preg_replace_callback(
            self::ENV_VARIABLE_PATTERN_SYMFONY,
            self::createEnvironmentVariableReplacer(),
            $value
        ) ?? $value;

        return $value;
    }

    /**
     * Creates a callback function for replacing environment variables.
     */
    private static function createEnvironmentVariableReplacer(): callable
    {
        return function (array $matches): string {
            /** @var string $variableName */
            $variableName = $matches[1];
            $environmentValue = self::getEnvironmentVariable($variableName);

            return $environmentValue !== false ? $environmentValue : '';
        };
    }

    /**
     * Resolves relative paths to absolute paths.
     */
    private static function resolveRelativePaths(string $value): string
    {
        if (!self::isRelativePath($value)) {
            return $value;
        }

        // Try realpath first for existing paths
        $realPath = realpath($value);
        if ($realPath !== false) {
            return $realPath;
        }

        // If path doesn't exist, manually resolve it
        return self::manuallyResolvePath($value);
    }

    /**
     * Checks if the given path is a relative path.
     */
    private static function isRelativePath(string $path): bool
    {
        return str_starts_with($path, './') || str_starts_with($path, '../');
    }

    /**
     * Manually resolves a relative path to an absolute path.
     */
    private static function manuallyResolvePath(string $relativePath): string
    {
        $workingDirectory = getcwd();
        $cleanedPath = ltrim($relativePath, './');
        $fullPath = $workingDirectory . '/' . $cleanedPath;

        return self::normalizePath($fullPath);
    }

    /**
     * Normalizes a path by resolving . and .. components.
     *
     * @param string $path The path to normalize
     * @return string The normalized absolute path
     */
    private static function normalizePath(string $path): string
    {
        $pathParts = explode('/', $path);
        $normalizedParts = [];

        foreach ($pathParts as $part) {
            if (self::shouldSkipPathPart($part)) {
                continue;
            }

            if (self::isParentDirectoryReference($part)) {
                self::navigateToParentDirectory($normalizedParts);
            } else {
                $normalizedParts[] = $part;
            }
        }

        return '/' . implode('/', $normalizedParts);
    }

    /**
     * Determines if a path part should be skipped during normalization.
     */
    private static function shouldSkipPathPart(string $part): bool
    {
        return $part === '' || $part === '.';
    }

    /**
     * Checks if the path part is a parent directory reference.
     */
    private static function isParentDirectoryReference(string $part): bool
    {
        return $part === '..';
    }

    /**
     * Navigates to the parent directory by removing the last part.
     *
     * @param array<string> $normalizedParts
     */
    private static function navigateToParentDirectory(array &$normalizedParts): void
    {
        if (!empty($normalizedParts)) {
            array_pop($normalizedParts);
        }
    }

    /**
     * Retrieves an environment variable value from various sources.
     *
     * Checks $_ENV, $_SERVER, and getenv() in that order.
     *
     * @param string $variableName The name of the environment variable
     * @return string|false The environment variable value or false if not found
     */
    private static function getEnvironmentVariable(string $variableName)
    {
        $value = $_ENV[$variableName] ?? $_SERVER[$variableName] ?? getenv($variableName);

        if ($value === false) {
            return false;
        }

        return self::convertToString($value);
    }

    /**
     * Converts a value to string, handling different scalar types appropriately.
     *
     * @param mixed $value
     */
    private static function convertToString($value): string
    {
        if (is_string($value)) {
            return $value;
        }

        // Convert scalar types only; otherwise, return empty string
        if (is_int($value) || is_float($value) || is_bool($value)) {
            return (string) $value;
        }

        return '';
    }
}
