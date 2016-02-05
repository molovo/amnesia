<?php

namespace Molovo\Amnesia\Driver;

use Molovo\Amnesia\Cache\Instance;
use Molovo\Amnesia\Config;
use Molovo\Amnesia\Interfaces\Driver;

class File implements Driver
{
    /**
     * The path in which cache files are stored.
     *
     * @var string|null
     */
    private $storePath = null;

    /**
     * The instance which is using this driver.
     *
     * @var Instance|null
     */
    private $instance = null;

    /**
     * Construct the driver instance.
     *
     * @param Config $config The instance config
     */
    public function __construct(Config $config, Instance $instance)
    {
        $this->instance  = $instance;
        $this->storePath = $config->store_path;
    }

    /**
     * The filename to store the cached value against.
     *
     * @param string $key The cache key
     *
     * @return string The filename in which to store value
     */
    public function filename($key)
    {
        return $this->storePath.DIRECTORY_SEPARATOR.$key;
    }

    /**
     * Get a value from the cache.
     *
     * @param string $key The key to fetch
     *
     * @return string The returned json string
     */
    public function get($key, $log = false)
    {
        $filename = $this->filename($key);

        // Check data exists for the key
        $data = file_exists($filename) ? file_get_contents($filename) : null;

        if ($data === null) {
            return;
        }

        // JSON decode only the top level, so we can get the metadata
        $data = json_decode($data, false, 2);

        // If expiry time has passed, clear the specified key,
        // and return null
        if ($data->expires !== null && time() > $data->expires) {
            $this->clear($key);

            return;
        }

        // Return the data object
        return $data->value;
    }

    /**
     * Set a value against a key in the cache.
     *
     * @param string   $key     The key to store against
     * @param string   $value   A json_encoded value
     * @param int|null $expires Optional expiry time in seconds from now
     */
    public function set($key, $value = null, $expires = null)
    {
        $filename = $this->filename($key);

        $data = [
            'value'   => $value,
            'expires' => $expires !== null ? time() + (int) $expires : null,
        ];

        file_put_contents($filename, json_encode($data));
    }

    /**
     * Get multiple values from the cache.
     *
     * @param array $keys An array of keys to get
     *
     * @return array An array of JSON objects
     */
    public function mget(array $keys = array())
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$this->instance->unkey($key)] = $this->get($key);
        }

        return $values;
    }

    /**
     * Set multiple values in the cache.
     *
     * @param array    $dictionary An array of keys and values to set
     * @param int|null $expires    Optional expiry time in seconds from now
     */
    public function mset(array $dictionary = array(), $expires = null)
    {
        foreach ($dictionary as $key => $value) {
            $this->set($key, $value, $expires);
        }
    }

    /**
     * Clear a single value from the cache.
     *
     * @param string $key The key to clear
     */
    public function clear($key)
    {
        $filename = $this->filename($key);

        if (file_exists($filename)) {
            unlink($filename);
        }
    }

    /**
     * Clear an array of values from the cache.
     *
     * @param array $key An array of keys to clear
     */
    public function mclear(array $keys = array())
    {
        foreach ($keys as $key) {
            $this->clear($key);
        }
    }

    /**
     * Get all keys within the namespace.
     *
     * @return array
     */
    public function keys($namespace)
    {
        $keys = glob($this->filename($namespace));

        foreach ($keys as &$filename) {
            $filename = str_replace($this->storePath, '', $filename);
        }

        return $keys;
    }

    /**
     * Flush all keys within a namespace from the cache.
     *
     * @param string $namespace The namespace to clear
     */
    public function flush($namespace)
    {
        $keys = $this->keys($namespace);

        $this->mclear($keys);
    }
}
