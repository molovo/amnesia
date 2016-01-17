<?php

namespace Molovo\Amnesia;

use Dotenv\Dotenv;
use Molovo\Amnesia\Cache\Instance;
use Molovo\Amnesia\Config;
use Molovo\Amnesia\Interfaces\Driver;

class Cache
{
    /**
     * An array of cache instances.
     *
     * @var Instance[]
     */
    private static $instances = [];

    /**
     * Cache config.
     *
     * @var Config|null
     */
    public static $config = null;

    /**
     * Bootstrap the amnesia library.
     *
     * @param array $config The config to initialize with
     */
    public static function bootstrap(array $config = [])
    {
        static::$config = new Config($config);
    }

    /**
     * Get the cache config.
     *
     * @return Config
     */
    public static function config()
    {
        return static::$config;
    }

    /**
     * Return a cache instance.
     *
     * @return Instance
     */
    public static function instance($name = null)
    {
        $name = $name ?: 'default';

        if (isset(static::$instances[$name])) {
            return static::$instances[$name];
        }

        return static::$instances[$name] = new Instance($name, static::$config->{$name});
    }

    /**
     * Get a value from the cache.
     *
     * @param string $key     The key to get
     * @param bool   $decode  Decode the value
     * @param bool   $asArray Return as an array
     *
     * @return mixed The value
     */
    public static function get($key, $decode = true, $asArray = false, Instance $instance = null)
    {
        $instance = $instance ?: static::instance('default');

        return $instance->get($key, $decode, $asArray);
    }

    /**
     * Store a value in the cache.
     *
     * @param string     $key     The key to set
     * @param mixed|null $value   The value to set
     * @param int|null   $expires Optional expiry time
     *
     * @return mixed The value
     */
    public static function set($key, $value = null, $expires = null, Instance $instance = null)
    {
        $instance = $instance ?: static::instance('default');
        $instance->set($key, $value, $expires);
    }

    /**
     * Get multiple values from the cache.
     *
     * @param string[] $key     An array of keys to get
     * @param bool     $decode  Decode the value
     * @param bool     $asArray Return as an array
     *
     * @return array The values
     */
    public static function mget(array $keys, $decode = true, $asArray = true, Instance $instance = null)
    {
        $instance = $instance ?: static::instance('default');

        return $instance->mget($keys, $decode, $asArray);
    }

    /**
     * Store multiple values in the cache.
     *
     * @param array    $dictionary An array of keys and values to set
     * @param int|null $expires    Optional expiry time
     */
    public static function mset(array $dictionary, $expires = null, Instance $instance = null)
    {
        $instance = $instance ?: static::instance('default');
        $instance->mset($dictionary, $expires);
    }

    /**
     * Clear a value from the cache.
     *
     * @param string $key The key to clear
     */
    public static function clear($key)
    {
        $instance = $instance ?: static::instance('default');
        $instance->clear($key);
    }

    /**
     * Clear multiple values from the cache.
     *
     * @param array $keys An array of keys to clear
     */
    public static function mclear(array $keys)
    {
        $instance = $instance ?: static::instance('default');
        $instance->mclear($keys);
    }

    /**
     * Flush the full contents of the cache.
     */
    public static function flush()
    {
        $instance = $instance ?: static::instance('default');
        $instance->flush();
    }
}
