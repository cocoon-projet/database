<?php
namespace Cocoon\FileSystem;

class File
{

    public static function __callStatic($name, $arguments)
    {
        $instance = FileSystem::class;
        return $instance->$name(...$arguments);
    }
}
