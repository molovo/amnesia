<?php

namespace Molovo\Amnesia\Cache;

use Molovo\Amnesia\Config;
use Molovo\Amnesia\Driver\File;
use Molovo\Amnesia\Driver\Memcached;
use Molovo\Amnesia\Driver\Predis;
use Molovo\Amnesia\Driver\Redis;
use Molovo\Amnesia\Interfaces\Driver;

class Instance
{
    /**
     * The name of the instance.
     *
     * @var string|null
     */
    public $name = null;

    /**
     * The cache key for namespacing.
     *
     * @var string|null
     */
    private $key = null;

    /**
     * The driver currently in use.
     *
     * @var Driver|null
     */
    private $driver = null;

    /**
     * An array of driver classes.
     *
     * @var string[]
     */
    private $drivers = [
        'file'      => File::class,
        'redis'     => Redis::class,
        'predis'    => Predis::class,
        'memcached' => Memcached::class,
    ];

    /**
     * Create a new instance of the cache and driver.
     *
     * @param string|null $name   The connection name
     * @param Config      $config The config for the instance
     */
    public function __construct($name = null, Config $config)
    {
        // If a name isn't provided, then we'll use the default
        $this->name   = $name ?: 'default';
        $config->name = $this->name;

        // Create a cache namespace key
        $this->key = hash('adler32', $name);

        // Initialise the driver
        $driverClass  = $this->drivers[$config->driver];
        $this->driver = new $driverClass($config, $this);

        // If the driver isn't created, throw an exception
        if (!($this->driver instanceof Driver)) {
            throw new InvalidDriverException($driver.' is not a valid driver.');
        }
    }

    /**
     * Namespace the key before setting it.
     *
     * @param string $key The key to namespace
     *
     * @return string The namespaced key
     */
    private function key($key)
    {
        return $this->key.'.'.$key;
    }

    /**
     * Encode a value ready for storage.
     *
     * @param mixed $value The value to encode
     *
     * @return string A JSON string
     */
    private function encode($value)
    {
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }

        return $value;
    }

    /**
     * Decode and return a value.
     *
     * @param string $value   A JSON string
     * @param bool   $asArray Return as an array
     *
     * @return mixed The decoded value
     */
    private function decode($value, $asArray = false)
    {
        return json_decode($value, $asArray);
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
    public function get($key, $decode = true, $asArray = false)
    {
        $key   = $this->key($key);
        $value = $this->driver->get($key);
        if ($decode && ($a = $this->decode($value, $asArray)) !== null) {
            return $a;
        }

        return $value;
    }

    /**
     * Store a value in the cache.
     *
     * @param string     $key     The key to set
     * @param mixed|null $value   The value to set
     * @param int|null   $expires Optional expiry time
     */
    public function set($key, $value = null, $expires = null)
    {
        $key = $this->key($key);
        if ($value === null) {
            $this->driver->clear($key);
        }
        $value = $this->encode($value);
        $this->driver->set($key, $value, $expires);
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
    public function mget($keys, $decode = true, $asArray = false)
    {
        foreach ($keys as &$key) {
            $key = $this->key($key);
        }

        $values = $this->driver->mget($keys);

        foreach ($values as &$value) {
            if ($decode && ($a = $this->decode($value, $asArray)) !== null) {
                $value = $a;
            }
        }

        return $values;
    }

    /**
     * Store multiple values in the cache.
     *
     * @param array    $dictionary An array of keys and values to set
     * @param int|null $expires    Optional expiry time
     */
    public function mset($dictionary, $expires = null)
    {
        $values = [];
        foreach ($dictionary as $k => $v) {
            $values[$this->key($k)] = $this->encode($v);
        }
        $this->driver->mset($values, $expires);
    }

    /**
     * Clear a value from the cache.
     *
     * @param string $key The key to clear
     */
    public function clear($key)
    {
        $key = $this->key($key);

        $this->driver->clear($key);
    }

    /**
     * Clear multiple values from the cache.
     *
     * @param array $keys An array of keys to clear
     */
    public function mclear(array $keys)
    {
        foreach ($keys as &$key) {
            $key = $this->key($key);
        }

        $this->driver->mclear($keys);
    }

    /**
     * Flush the full contents of the cache.
     */
    public function flush()
    {
        $namespace = $this->key('*');

        $this->driver->flush($namespace);
    }
}
