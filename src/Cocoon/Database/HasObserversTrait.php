<?php
declare(strict_types=1);

namespace Cocoon\Database;

trait HasObserversTrait
{
    protected static $observers = [];

    protected static $observerClass = null;

    public static function beforeSave(callable $callback)
    {
        static::$observers['beforeSave'] = $callback;
    }

    public static function afterSave(callable $callback)
    {
        static::$observers['afterSave'] = $callback;
    }

    public static function beforeUpdate(callable $callback)
    {
        static::$observers['beforeUpdate'] = $callback;
    }

    public static function afterUpdate(callable $callback)
    {
        static::$observers['afterUpdate'] = $callback;
    }

    public static function beforeDelete(callable $callback)
    {
        static::$observers['beforeDelete'] = $callback;
    }

    public static function afterDelete(callable $callback)
    {
        static::$observers['afterDelete'] = $callback;
    }

    protected function runObserver($observerName)
    {
        if (isset(static::$observers[$observerName])) {
            static::$observers[$observerName]($this);
        }
        if (static::$observerClass != null) {
            if (method_exists(static::$observerClass, $observerName)) {
                $observer = static::$observerClass;
                (new $observer())->$observerName($this);
            }
        }
    }

    public static function observer($class)
    {
        static::$observerClass = $class;
    }
}
