<?php

namespace Molovo\Amnesia\Driver;

use Molovo\Amnesia\Config;
use Molovo\Amnesia\Interfaces\Driver;

class Memcached implements Driver
{
    /**
     * The client to which we are connected.
     *
     * @var \Memcached|null
     */
    private $client = null;

    /**
     * Construct the driver instance.
     *
     * @param Config $config The instance config
     */
    public function __construct(Config $config)
    {
        $this->client = new \Memcached($config->name);

        if (count($this->client->getServerList()) === 0) {
            $this->client->addServers($config->servers->toArray());
        }
    }

    /**
     * Get a value from the cache.
     *
     * @param string $key The key to fetch
     *
     * @return string The json_encoded value
     */
    public function get($key)
    {
        return $this->client->get($key);
    }

    /**
     * Set a value against a key in the cache.
     *
     * @param string $key     The key to store against
     * @param string $value   A json_encoded value
     * @param int    $expires Optional expiry time in seconds from now
     */
    public function set($key, $value = null, $expires = 0)
    {
        if ($value === null) {
            return $this->clear($key);
        }

        return $this->client->set($key, $value, $expires);
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
        return array_values($this->client->getMulti($keys));
    }

    /**
     * Set multiple values in the cache.
     *
     * @param array    $dictionary An array of keys and values to set
     * @param int|null $expires    Optional expiry time in seconds from now
     */
    public function mset(array $dictionary = array(), $expires = 0)
    {
        return $this->client->setMulti($dictionary, $expires);
    }

    /**
     * Clear a single value from the cache.
     *
     * @param string $key The key to clear
     */
    public function clear($key)
    {
        return $this->client->delete($key);
    }

    /**
     * Clear an array of values from the cache.
     *
     * @param array $key An array of keys to clear
     */
    public function mclear(array $keys = array())
    {
        return $this->client->deleteMulti($keys);
    }

    /**
     * Flush all keys within a namespace from the cache.
     *
     * @param string $namespace The namespace to clear
     */
    public function flush($namespace)
    {
        $keys = $this->client->getAllKeys();

        $keysToFlush = [];
        foreach ($keys as $key) {
            if (strpos($key, $namespace) === 0) {
                $keysToFlush[] = $key;
            }
        }

        return $this->client->flush($keysToFlush);
    }
}
