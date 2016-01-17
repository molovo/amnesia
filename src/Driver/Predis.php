<?php

namespace Molovo\Amnesia\Driver;

use Molovo\Amnesia\Config;
use Molovo\Amnesia\Interfaces\Driver;
use Predis\Client;

class Predis implements Driver
{
    /**
     * The client to which we are connected.
     *
     * @var Client|null
     */
    private $client = null;

    /**
     * Construct the driver instance.
     *
     * @param Config $config The instance config
     */
    public function __construct(Config $config)
    {
        if ($config->socket) {
            $this->client = new Client('unix://'.$config->socket);
        } else {
            $this->client = new Client('tcp://'.($config->host ?: '127.0.0.1').':'.($config->port ?: 6379));
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
     * @param string   $key     The key to store against
     * @param string   $value   A json_encoded value
     * @param int|null $expires Optional expiry time in seconds from now
     */
    public function set($key, $value = null, $expires = null)
    {
        if ($value === null) {
            return $this->clear($key);
        }

        if ($expires === null) {
            return $this->client->set($key, $value);
        }

        return $this->client->setex($key, $expires, $value);
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
        return $this->client->mget($keys);
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

        return;
    }

    /**
     * Clear a single value from the cache.
     *
     * @param string $key The key to clear
     */
    public function clear($key)
    {
        return $this->client->del([$key]);
    }

    /**
     * Clear an array of values from the cache.
     *
     * @param array $key An array of keys to clear
     */
    public function mclear(array $keys = array())
    {
        return $this->client->del($keys);
    }

    /**
     * Flush all keys within a namespace from the cache.
     *
     * @param string $namespace The namespace to clear
     */
    public function flush($namespace)
    {
        $keys = $this->client->keys($namespace);

        return $this->mclear($keys);
    }

    /**
     *
     */
    public function __destruct()
    {
        $this->client->disconnect();
        $this->client = null;
    }
}
