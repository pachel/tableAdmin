<?php
namespace Pachel\TableAdmin\Traits;


trait instance {
    /**
     * @var array<string, static>
     */
    protected static $_instances = [];

    public static function instance(...$args)
    {
        $class = static::class; // Dinamikusan az a gyerek/hívó osztály, amelyik használja

        if (!isset(self::$_instances[$class])) {
            self::$_instances[$class] = !empty($args)
                ? (new \ReflectionClass($class))->newInstanceArgs($args)
                : new static();
        }

        return self::$_instances[$class];
    }
}