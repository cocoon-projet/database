<?php
declare(strict_types=1);

namespace Cocoon\FileSystem;

class File
{
    private static ?FileSystem $instance = null;
    private static string $basePath = '';

    public static function initialize(string $basePath): void
    {
        self::$basePath = $basePath;
        self::$instance = null;
    }

    public static function __callStatic(string $name, array $arguments)
    {
        if (self::$instance === null) {
            if (empty(self::$basePath)) {
                throw new \RuntimeException(
                    'FileSystem not initialized. Call File::initialize() first with a base path.'
                );
            }
            self::$instance = new FileSystem(self::$basePath);
        }
        return self::$instance->$name(...$arguments);
    }
}
