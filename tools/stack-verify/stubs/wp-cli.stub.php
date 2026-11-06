<?php

class WP_CLI
{
    /**
     * @param callable $callable
     * @param array<string, mixed> $args
     */
    public static function add_command(string $name, $callable, array $args = []): bool
    {
    }

    public static function log(string $message): void
    {
    }

    public static function success(string $message): void
    {
    }

    /**
     * @return never
     */
    public static function error(string $message): void
    {
    }
}
